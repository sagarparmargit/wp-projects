# WP Projects

WP Projects registers a Projects post type and provides a public REST API and a shortcode with AJAX-powered, paginated project loading.

## Installation

1. Copy the `wp-projects` directory into `wp-content/plugins/`.
2. Activate **WP Projects Managment** from the WordPress Plugins screen.
3. Optionally install Advanced Custom Fields (ACF) and create the project fields below to manage their values in the editor.

## Projects post type

The plugin registers the public `projects` post type. It supports:

- Title
- Editor
- Featured image

Create these ACF fields for the Projects post type if you want to manage them from the editor. The plugin reads their values from post meta, so ACF is not required at runtime. Use the field names exactly as shown:

| Label | Field name | Purpose |
| --- | --- | --- |
| Client Name | `client_name` | Client displayed on project cards and returned by the API |
| Project Status | `project_status` | Project status; use `Active` or `Completed` for status filtering |
| Budget | `budget` | Budget displayed on project cards and returned by the API |

Projects are queried only when their WordPress post status is `publish`.

## REST API

The endpoint is public and uses the WordPress REST API:

```text
GET /wp-json/wp-projects/v1/projects
```

Query parameters:

| Parameter | Default | Allowed values / range | Description |
| --- | --- | --- | --- |
| `status` | `all` | `all`, `active`, `completed` | Return all published projects or filter by `project_status` |
| `page` | `1` | Integer, minimum `1` | Page number |
| `per_page` | `10` | Integer, `1`–`100` | Number of projects per page |

Examples:

```text
/wp-json/wp-projects/v1/projects
/wp-json/wp-projects/v1/projects?status=active&page=1&per_page=10
/wp-json/wp-projects/v1/projects?status=completed&page=2&per_page=5
```

The response contains a `projects` array and `pagination` metadata:

```json
{
  "projects": [
    {
      "id": 123,
      "title": "Project title",
      "url": "https://example.com/projects/project-title/",
      "thumbnail": "https://example.com/wp-content/uploads/project.jpg",
      "client_name": "Example Client",
      "project_status": "Active",
      "budget": "50000"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 10,
    "total_projects": 1,
    "total_pages": 1
  }
}
```

## Shortcode

Use the shortcode in a post, page, or shortcode-enabled widget:

```text
[projects]
```

The `status` attribute defaults to `all`:

```text
[projects status="all"]
[projects status="active"]
[projects status="completed"]
```

The shortcode loads its first page through WordPress AJAX. Each project card displays its title, featured image when available, client name, project status, and budget. Missing field values display as “Not specified.”

The single **Load More** button follows the shortcode's `status` attribute and appends the next page without duplicating cards already displayed. The shortcode loads 10 projects per page. The button is disabled when there are no further results.

## AJAX

The shortcode sends a POST request to `wp-admin/admin-ajax.php` using the `wpp_load_projects` action. It is registered for both logged-in and logged-out visitors.

The request includes:

| Parameter | Description |
| --- | --- |
| `action` | `wpp_load_projects` |
| `nonce` | Nonce localized by the shortcode |
| `status` | `all`, `active`, or `completed` |
| `page` | Requested page number |
| `per_page` | Page size, from 1 to 100 |

Successful requests return project data and pagination metadata using the standard `wp_send_json_success()` response format. Both the AJAX handler and REST API reuse the query in `WP_Project_CPT::get_projects()`.

## Plugin files

- `wp-projects.php` loads the plugin classes.
- `includes/class.cpt.php` registers the post type and contains the shared paginated project query.
- `includes/class.api.php` registers and handles the REST endpoint.
- `public/class.ajax.php` registers the shortcode and AJAX endpoint.
- `src/projects.js` renders cards and requests additional pages using jQuery AJAX.
- `src/projects.css` styles the shortcode output.
