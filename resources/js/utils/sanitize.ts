import DOMPurify from 'dompurify';
import { marked } from 'marked';

marked.setOptions({ breaks: true, gfm: true });

/**
 * Parse markdown and sanitize the resulting HTML.
 * Safe for use with v-html — strips any XSS vectors.
 */
export function safeMarkdown(text: string): string {
    if (!text) return '';
    return DOMPurify.sanitize(marked.parse(text) as string);
}

/**
 * Inline markdown: only bold (**text**) → <strong>text</strong>.
 * Sanitized for v-html safety.
 */
export function safeInlineMd(text: string): string {
    if (!text) return '';
    const html = text.replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>');
    return DOMPurify.sanitize(html);
}
