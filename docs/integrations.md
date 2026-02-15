# External Integrations

## Linear (Project Management)

Team **POST** in the `medduties` workspace. API key: `$LINEAR_API_KEY` env var.

### Querying issues via GraphQL

```bash
curl -s -X POST 'https://api.linear.app/graphql' \
  -H 'Content-Type: application/json' \
  -H "Authorization: $LINEAR_API_KEY" \
  -d '{"query":"{ team(id: \"506cce46-72bf-4ee9-80d6-754659668b7b\") { issues(first: 50) { nodes { identifier title state { name } priority priorityLabel assignee { name } } } } }"}'
```

### Creating / updating issues

```bash
# Create an issue
curl -s -X POST 'https://api.linear.app/graphql' \
  -H 'Content-Type: application/json' \
  -H "Authorization: $LINEAR_API_KEY" \
  -d '{"query":"mutation { issueCreate(input: { teamId: \"506cce46-72bf-4ee9-80d6-754659668b7b\", title: \"Issue title\", description: \"Details\" }) { success issue { identifier url } } }"}'

# Update issue state (use stateId from workflow states query)
curl -s -X POST 'https://api.linear.app/graphql' \
  -H 'Content-Type: application/json' \
  -H "Authorization: $LINEAR_API_KEY" \
  -d '{"query":"mutation { issueUpdate(id: \"<issue-uuid>\", input: { stateId: \"<state-uuid>\" }) { success } }"}'
```

### Key IDs
- **Team POST**: `506cce46-72bf-4ee9-80d6-754659668b7b`
- Always use GraphQL API — do not rely on browser automation for Linear
- Pipe output through `python3 -m json.tool` for readable formatting

---

## Craft (Documentation / Knowledge Base)

### Connection
- **Base URL**: `https://connect.craft.do/links/3SxffBwjsYS/api/v1`
- **Space ID**: `22c77e8f-27ff-3921-2f71-1d1f300aad2f`
- **Auth**: No API key needed — authenticated via the connection link

### Common Operations

```bash
BASE="https://connect.craft.do/links/3SxffBwjsYS/api/v1"

curl -s "$BASE/folders" | python3 -m json.tool                    # folder structure
curl -s -H "Accept: text/markdown" "$BASE/blocks?id=DOCUMENT_ID"  # read doc (markdown)
curl -s "$BASE/documents/search?query=SEARCH_TERM"                # search
curl -s "$BASE/tasks?scope=active" | python3 -m json.tool         # active tasks
```

- **Document ID = root block ID** — use with `GET /blocks?id={docId}`
- MCP tools (`mcp__claude_ai_Craft__*`) also available as alternative interface
- Safe: `GET` requests. Unsafe: permanent deletions without backup.
