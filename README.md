# Blumoo Article Explorer

A compact WordPress portfolio demo: search published articles, filter by category, and paginate results without reloading the page. PHP renders the initial results; native JavaScript enhances the experience through a custom REST endpoint.

## Purpose and provenance

Created as a new code sample for a front-end / WordPress developer application, rather than extracted from an existing client project. The sample demonstrates PHP, WordPress hooks, semantic HTML, CSS Grid/Flexbox, native DOM APIs, event handling, `fetch()`, and defensive request handling.

**Authorship:** The initial implementation and documentation were generated with OpenAI Codex assistance. This version has not yet been reviewed, customized, or deployed by Gregory Moore. Update this statement after your own review to accurately describe your contributions. This is a demonstration, not evidence of previous production delivery.

## Requirements

- WordPress 6.3 or newer; PHP 7.4 or newer.
- A modern browser supporting `fetch`, `AbortController`, and optional chaining.
- Some published posts and categories for meaningful results.
- No JavaScript framework, npm dependencies, build step, API keys, or database migrations.

## Installation

1. Copy the `blumoo-article-explorer` directory into `wp-content/plugins/`, or upload the ZIP through **Plugins → Add New → Upload Plugin**.
2. Activate **Blumoo Article Explorer**.
3. Add a Shortcode block to a page with:

   ```text
   [blumoo_article_explorer]
   ```

4. View the published page and try search, categories, and pagination.

Multiple shortcode instances have independent state and unique label IDs. Do not install this demo on a client site before validating it with that site's theme and plugins.

## Reviewer entry points

| File | What to inspect |
| --- | --- |
| `blumoo-article-explorer.php` | Query constraints, REST argument validation, shared escaped rendering, shortcode markup, asset loading |
| `assets/explorer.js` | Native event handling, state committed only after success, aborting older requests, loading/error feedback |
| `assets/explorer.css` | Responsive layout, scoped styles, visible focus, flexible form controls |

## How it works

The shortcode renders the first six posts in PHP so content and links are available before JavaScript runs. The form has a native WordPress search fallback. With JavaScript enabled, submitting the form requests a filtered page from:

```text
GET /wp-json/blumoo-explorer/v1/articles?search=design&category=0&page=1
```

Example response shape:

```json
{
  "html": "<li class=\"bae-card\">…</li>",
  "total": 14,
  "pages": 3
}
```

The endpoint returns HTML produced by the same PHP card renderer as the initial page. This keeps rendering and escaping in one place. JavaScript inserts only that same-origin response into the results list. If a Content Security Policy requires Trusted Types, adapt this insertion path before deployment.

## Design decisions

- **Progressive enhancement:** Existing article links and the native search form work without JavaScript. Without JavaScript, searches navigate to the theme's standard search results; inline pagination is hidden.
- **Explicit search submission:** Requests run on submission or pagination, rather than on every keystroke. Pagination uses the last successfully applied filters, even if the user edits the form without submitting.
- **Race handling:** `AbortController` cancels superseded requests, and a sequence counter prevents stale responses or cleanup from changing newer results.
- **Failure recovery:** Previous results and pagination state remain intact when a request fails. The status explains how to retry.
- **Accessibility intent:** Native controls, explicit labels, semantic headings/list markup, visible focus, a polite status region, and `aria-busy`. Focus stays on the initiating control. These choices do not constitute WCAG certification.
- **Public read-only API:** No nonce is needed for an unauthenticated public GET endpoint. Queries explicitly exclude unpublished and password-protected posts. Input is bounded and validated; rendered content is escaped at output.
- **Modest scope:** Six results per page, at most 100 accessible pages, no external services. The reported total is the full match count even when the accessible page count is capped.
- **Asset tradeoff:** The small CSS file loads across the front end to support shortcodes rendered after the document head. JavaScript is enqueued only when the shortcode renders and runs in the footer.
- **Plain CSS:** Custom properties, Grid, and Flexbox demonstrate the layout without requiring a compiler. SCSS could be introduced in a larger project.

## Validation status

JavaScript syntax was checked with `node --check`. This workspace has no PHP executable or running WordPress installation, so PHP linting, WordPress integration, browser behavior, and accessibility have **not** been verified here. No production-readiness claim is made.

Before submitting the sample, run:

```sh
php -l blumoo-article-explorer.php
node --check assets/explorer.js
```

Then test in a local WordPress installation:

- Publish at least eight sample posts in two categories; confirm initial rendering, search, category filtering, and both pagination directions.
- Try a search with no matches, quotes, ampersands, and HTML-like text; confirm content is escaped and the empty message appears.
- Verify drafts, private posts, and password-protected posts never appear in REST results.
- Call the endpoint with `page=0`, `page=101`, `category=-1`, and an overlong search; confirm REST validation rejects invalid arguments.
- Simulate a failed request and slow network; confirm prior results survive and newer searches win.
- Disable JavaScript; confirm article links and native search work.
- Add two shortcode instances; confirm independent filters and unique labels.
- Test keyboard navigation, screen-reader status announcements, 200% zoom, narrow screens, and theme compatibility.

## Deliberate limitations / next steps

English JavaScript messages are not yet wired into WordPress translation catalogs; PHP UI strings are translation-ready. Filter state is not stored in the URL. There are no thumbnail images, custom post types, automated integration tests, caching policy, or custom rate limiting. At larger scale, assess query costs, request limits, and caching. For sites with membership restrictions on otherwise published posts, integrate the membership plugin's visibility rules before exposing this endpoint.

## Preparing a GitHub submission

Create a repository such as `wordpress-article-explorer`, upload these source files with this README at the root, and describe it as a **WordPress article explorer portfolio demo**. Review and customize the code, complete the local checks, and revise the authorship and validation sections truthfully.

Submit the repository URL as the code sample. A live demo can be a second link, but it does not replace inspectable source code. Be ready to explain the shared renderer, REST permissions, race handling, and progressive enhancement decisions.

## References

- [WordPress REST route registration](https://developer.wordpress.org/reference/functions/register_rest_route/)
- [WordPress custom endpoints](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/)
- [WordPress script enqueueing](https://developer.wordpress.org/reference/functions/wp_enqueue_script/)

## License

GPL-2.0-or-later. See [GNU GPL version 2](https://www.gnu.org/licenses/old-licenses/gpl-2.0.html). No client code, credentials, or customer data are included.
