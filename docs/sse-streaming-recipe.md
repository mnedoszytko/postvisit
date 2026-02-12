# SSE Streaming Recipe: Anthropic Claude + PHP

> Battle-tested recipe for progressive token-by-token streaming from Claude API through PHP to the browser. Written after debugging a buffering issue where all tokens arrived at once — root cause was the SDK itself, not Nginx or PHP.

## The Critical Lesson (read this first)

**The official Anthropic PHP SDK (`anthropic-ai/sdk` v0.5.0) does NOT truly stream.** It uses PSR-18 `sendRequest()` which downloads the entire HTTP response body into memory before returning. The SDK's `createStream()` iterates over already-buffered data — all tokens arrive at once regardless of your Nginx/PHP flush settings.

**If you use `$client->messages->createStream(...)` directly, you will NOT get progressive streaming.** You'll get the illusion of streaming (a generator that yields events), but the HTTP response was already fully downloaded before the first yield.

### How we proved it

1. `curl --trace-time` directly to `api.anthropic.com` — tokens arrive progressively over seconds
2. `curl --trace-time` to our PHP endpoint using the SDK — ALL data arrives in 1ms (buffered in PHP)
3. Raw curl with `CURLOPT_WRITEFUNCTION` — tokens arrive progressively again

### The fix

Bypass the SDK for streaming calls. Use PHP's native curl with `CURLOPT_WRITEFUNCTION` callback via `curl_multi` (for generator compatibility). Non-streaming calls (`create()`) can still use the SDK normally.

---

## Architecture

```
Browser (EventSource / fetch)
    ↓ SSE connection
PHP Backend (Laravel response()->stream())
    ↓ raw curl + CURLOPT_WRITEFUNCTION
Anthropic API (https://api.anthropic.com/v1/messages)
```

## Dependencies

```bash
composer require anthropic-ai/sdk guzzlehttp/guzzle nyholm/psr7
```

- `anthropic-ai/sdk` — used for non-streaming calls (`create()`) and type definitions
- `guzzlehttp/guzzle` + `nyholm/psr7` — PSR-17/PSR-18 implementations required by the SDK
- For streaming calls: raw `curl_multi` (built into PHP, no extra deps)

## Backend: True Progressive Streaming (the working approach)

### Raw curl with curl_multi (Generator pattern)

This is what actually works for progressive streaming in a generator context:

```php
private function rawCurlStream(array $body, bool $withThinking): Generator
{
    $apiKey = config('anthropic.api_key');
    $buffer = '';

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
            'Accept: text/event-stream',
        ],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => function ($ch, $data) use (&$buffer) {
            $buffer .= $data;
            return strlen($data);
        },
    ]);

    $mh = curl_multi_init();
    curl_multi_add_handle($mh, $ch);

    $running = null;
    do {
        curl_multi_exec($mh, $running);

        // Parse complete SSE events from the buffer
        while (($pos = strpos($buffer, "\n\n")) !== false) {
            $chunk = substr($buffer, 0, $pos);
            $buffer = substr($buffer, $pos + 2);

            foreach (explode("\n", $chunk) as $line) {
                if (!str_starts_with($line, 'data: ')) continue;

                $payload = substr($line, 6);
                $decoded = json_decode($payload, true);
                if (!$decoded) continue;

                $type = $decoded['type'] ?? '';

                if ($type === 'error') {
                    throw new \RuntimeException($decoded['error']['message'] ?? 'API error');
                }

                if ($type !== 'content_block_delta') continue;

                $deltaType = $decoded['delta']['type'] ?? '';
                if ($deltaType === 'thinking_delta') {
                    yield ['type' => 'thinking', 'content' => $decoded['delta']['thinking']];
                } elseif ($deltaType === 'text_delta') {
                    yield ['type' => 'text', 'content' => $decoded['delta']['text']];
                }
            }
        }

        if ($running) {
            curl_multi_select($mh, 0.05); // Wait briefly for more data
        }
    } while ($running);

    curl_multi_remove_handle($mh, $ch);
    curl_multi_close($mh);
    curl_close($ch);
}
```

**Why `curl_multi` instead of `curl_exec`?** `curl_exec()` blocks until the request completes — you can't yield from inside `CURLOPT_WRITEFUNCTION`. `curl_multi_exec()` is non-blocking: it processes available data, lets you parse and yield, then loops back for more.

### Alternative: Direct echo in response()->stream() context

If you don't need a generator (e.g., a simple endpoint), you can use `curl_exec` + `CURLOPT_WRITEFUNCTION` that echoes directly:

```php
return response()->stream(function () use ($question) {
    while (ob_get_level() > 0) ob_end_flush();
    @ini_set('zlib.output_compression', '0');
    @ini_set('implicit_flush', '1');

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [/* ... */],
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_WRITEFUNCTION => function ($ch, $data) {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                if (!str_starts_with(trim($line), 'data: ')) continue;
                $decoded = json_decode(substr(trim($line), 6), true);
                if ($decoded && ($decoded['type'] ?? '') === 'content_block_delta') {
                    $delta = $decoded['delta'];
                    if ($delta['type'] === 'text_delta') {
                        echo 'data: ' . json_encode(['text' => $delta['text']]) . "\n\n";
                        flush();
                    }
                }
            }
            return strlen($data);
        },
    ]);
    curl_exec($ch);
    curl_close($ch);

    echo "data: [DONE]\n\n";
    flush();
}, 200, [
    'Content-Type' => 'text/event-stream',
    'Cache-Control' => 'no-cache',
    'X-Accel-Buffering' => 'no',
    'Content-Encoding' => 'none',
]);
```

## API Request Body Format

```php
$body = [
    'model' => 'claude-opus-4-6',
    'max_tokens' => 4096,
    'stream' => true,  // CRITICAL — must be true for SSE from Anthropic API
    'system' => 'You are a helpful assistant.',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello'],
    ],
];

// With extended thinking:
$body['thinking'] = ['type' => 'enabled', 'budget_tokens' => 8000];
$body['max_tokens'] = 16000; // Must be higher when thinking is enabled
```

## SSE Response Headers

```php
'Content-Type' => 'text/event-stream',
'Cache-Control' => 'no-cache',
'Connection' => 'keep-alive',
'X-Accel-Buffering' => 'no',        // Tells Nginx not to buffer
'Content-Encoding' => 'none',        // Prevents gzip buffering
```

## Frontend: EventSource (simplest)

```javascript
const es = new EventSource(`/api/chat?question=${encodeURIComponent(question)}`);

es.onmessage = (event) => {
    if (event.data.trim() === '[DONE]') {
        es.close();
        return;
    }
    const parsed = JSON.parse(event.data);
    if (parsed.thinking) {
        appendThinking(parsed.thinking);
    } else if (parsed.text) {
        appendText(parsed.text);
    }
};

es.onerror = () => {
    es.close();
};
```

**Limitation:** EventSource only supports GET. For POST with long conversation history, use `fetch` + `ReadableStream`:

```javascript
const response = await fetch('/api/chat', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: question }),
});

const reader = response.body.getReader();
const decoder = new TextDecoder();
let buffer = '';

while (true) {
    const { done, value } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });
    const lines = buffer.split('\n\n');
    buffer = lines.pop(); // Keep incomplete line

    for (const line of lines) {
        if (!line.startsWith('data: ')) continue;
        const payload = line.slice(6).trim();
        if (payload === '[DONE]') return;
        const parsed = JSON.parse(payload);
        // Handle parsed.text, parsed.thinking, etc.
    }
}
```

## Anti-Buffering Checklist (all layers)

Streaming can be buffered at 5 levels. Check ALL of them:

### Level 1: Anthropic SDK (THE MOST COMMON BLOCKER)
- **Problem:** PSR-18 `sendRequest()` downloads entire response before returning
- **Fix:** Bypass SDK for streaming — use raw curl with `CURLOPT_WRITEFUNCTION`
- **Verify:** `curl --trace-time` to your PHP endpoint shows timestamps spread over seconds, not clustered

### Level 2: PHP output buffering
```php
while (ob_get_level() > 0) { ob_end_flush(); }
@ini_set('output_buffering', '0');
@ini_set('zlib.output_compression', '0');
@ini_set('implicit_flush', '1');
ob_implicit_flush(true);
```

### Level 3: PHP-FPM / FastCGI
In your Nginx config for the PHP location:
```nginx
fastcgi_buffering off;
```

### Level 4: Nginx reverse proxy
```nginx
proxy_buffering off;        # If using proxy_pass
postpone_output 0;          # Don't wait for minimum output size
tcp_nodelay on;             # Send small packets immediately
```

Also add response header: `X-Accel-Buffering: no`

### Level 5: HTTP/2 frame aggregation
HTTP/2 can aggregate small DATA frames. Options:
- Disable HTTP/2 for SSE endpoints: comment out `http2 on;`
- Or set `http2_chunk_size 64;` (forces smaller frames)
- In practice, this is rarely the issue — Levels 1-3 are almost always the cause

### Level 6: CDN / WAF / Cloudflare
- Disable response buffering/compression for SSE endpoints
- Don't cache SSE responses
- Set `Content-Encoding: none` to prevent CDN gzip buffering

## Diagnostics Workflow

When streaming doesn't work, use this systematic approach:

### Step 1: Test the API directly
```bash
curl --trace-time -sN https://api.anthropic.com/v1/messages \
  -H "x-api-key: $ANTHROPIC_API_KEY" \
  -H "anthropic-version: 2023-06-01" \
  -H "content-type: application/json" \
  -d '{"model":"claude-opus-4-6","max_tokens":100,"stream":true,"messages":[{"role":"user","content":"Say hi"}]}'
```
**Expected:** Timestamps spread over seconds. If not → API issue (unlikely).

### Step 2: Test your PHP endpoint
```bash
curl --trace-time -sN https://your-app.test/api/chat?question=Hello
```
**Expected:** Same spread timestamps. If all data arrives at once → PHP/SDK buffering.

### Step 3: Identify the buffer layer
- If Step 1 streams but Step 2 doesn't → problem is in PHP (Level 1 or 2)
- If both stream but browser doesn't → problem is in Nginx (Level 3-4) or JS
- If neither streams → check if `stream: true` is set in the API request body

### Step 4: Verify with timing test in PHP
```php
// In artisan tinker:
$start = microtime(true);
foreach ($client->stream('Hi', [['role'=>'user','content'=>'Say hi']]) as $chunk) {
    $elapsed = round(microtime(true) - $start, 3);
    echo "[{$elapsed}s] {$chunk}\n";
}
```
**Expected:** Timestamps spread over time, not all the same.

## Extended Thinking SSE Event Types

With extended thinking enabled, the Anthropic API sends these delta types:

| API delta type | Our SSE event | Description |
|---------------|---------------|-------------|
| `thinking_delta` | `{"thinking": "..."}` | Internal reasoning tokens |
| `text_delta` | `{"text": "..."}` | Visible response tokens |
| (end of stream) | `[DONE]` | Stream complete signal |

Thinking tokens arrive first, then text tokens. Frontend should show thinking in a collapsible section.

## Security

- **Never expose `ANTHROPIC_API_KEY` to the frontend** — all API calls go through your backend
- Validate and sanitize user input before sending to the API
- Add rate limiting and authentication to SSE endpoints in production
- Release PHP session lock before streaming (`session()->save()` in Laravel) to prevent blocking concurrent requests
- Set `ignore_user_abort(true)` in the stream callback to prevent orphaned connections
- Set `set_time_limit(0)` — streaming can take 30+ seconds with extended thinking

## PostVisit.ai Implementation Reference

Our production implementation lives in:
- `app/Services/AI/AnthropicClient.php` — `rawCurlStream()` method (generator-based)
- `app/Http/Controllers/Api/ChatController.php` — SSE endpoint
- `app/Http/Controllers/Api/ExplainController.php` — SSE endpoint
- `resources/js/composables/useSse.ts` — frontend SSE consumer

Performance before/after the fix:
- **Time to first token:** 7.2s → 1.8s
- **Token delivery:** All at once → Progressive over ~5s
- **User experience:** Blank screen for 7s → Thinking indicator at 2s, text streaming at ~3s
