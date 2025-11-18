# MentorPro Platform – New Client Onboarding & Matching Setup Guide

This guide explains how to add a new client to the MentorPro Platform plugin, set up their data collection form, and configure the matching algorithm with client-specific traits.

---

## 2. Connect the Gravity Form

Each client typically has their own **mentor/mentee intake form** in Gravity Forms.

- Create a new Gravity Form or clone an existing one.
- Map form fields to **post meta keys** or **Pods fields** used in your plugin.
  - Example: `field_123` → `mpro_trait_extraversion`
- In the submission handler (`MP_Users::upsert_and_assign` or a client-specific handler class), ensure data is saved to the correct custom fields.
- Confirm the form populates a `mentor_submission` post for each submission, tagged with `assigned_client`.

---

## 3. Define the Matching Schema

Each client may have different **traits** or priorities.  
Schemas live in **client-specific matching classes**, e.g.:

- `class-leap4ed-matching.php`
- `class-salem-matching.php`
- `class-matching-base.php` (fallback)

Steps:

1. Copy `class-matching-base.php` to a new file (e.g., `class-myclient-matching.php`).
2. Define `get_all_trait_settings()` with traits relevant to this client:
   ```php
   protected function get_all_trait_settings() {
     return [
       'Career Interests' => ['cap' => 10, 'description' => 'Career Interests'],
       'Hobbies'          => ['cap' => 5,  'description' => 'Shared Hobbies'],
       'Personality'      => ['cap' => 5,  'description' => 'TIPI Personality Traits'],
       'Language Match'   => ['cap' => 8,  'description' => 'Common Languages'],
       // Add/remove traits as needed
     ];
   }
   ```
3. Add scoring rules for new traits inside `generate_matching_report()`.

---

## 4. Register the Client in the Shortcode

The `[mentor_matches client_id="xyz"]` shortcode loads the correct matching class:

```php
switch ($client_id) {
  case 'leap4ed-chp':
    require_once 'class-leap4ed-matching.php';
    $matching = new Leap4Ed_Matching($client_id);
    break;

  case 'salem':
    require_once 'class-salem-matching.php';
    $matching = new Salem_Matching($client_id);
    break;

  case 'myclient':
    require_once 'class-myclient-matching.php';
    $matching = new MyClient_Matching($client_id);
    break;

  default:
    return "<p>Unknown client ID: $client_id</p>";
}
```

---

## 5. Test the Matching Workflow

- Submit sample mentor and mentee entries via the client’s form.
- Run `[mentor_matches client_id="myclient"]` on a test page.
- Confirm:
  - Matches are generated.
  - Unmatched mentees are correctly flagged (due to capacity vs language).
  - CSV export works with `📥 Download Matches CSV`.

---

## 6. Maintain & Evolve

When adding new traits or altering weighting:

- Update the client’s matching class.
- Document the new schema inside the class for future reference.
- Keep `class-matching-base.php` minimal — only shared defaults go here.

---

## 7. Quick Checklist

When onboarding a new client:

- [ ] Add a new **Client post** (ID, logo, groups).  
- [ ] Create/configure **Gravity Form** and map fields.  
- [ ] Add a new **client-specific matching class**.  
- [ ] Register the new client in the shortcode switch.  
- [ ] Test with sample data.  
- [ ] Export CSV to validate outputs.  


## 12) Configure which fields are exported to CSV

The CSV download in the matching report is configurable so you can decide **which columns** to include and **how to populate each row** per client.

### A. Choose columns (headers)

Use the `mpro_matches_csv_columns` filter to define the column order and labels. Return an **ordered list of column headers**.

```php
/**
 * Change CSV column headers for a specific client (example: "salem").
 */
add_filter('mpro_matches_csv_columns', function(array $columns, string $client_id){
    if ($client_id !== 'salem') {
        return $columns; // keep defaults for other clients
    }

    // Define the exact header order you want
    return [
        'Mentee Name',
        'Mentee ID',
        'Mentor Name',
        'Mentor ID',
        'Score %',
        'Matched Fields',
        // Add any custom columns you want to expose:
        'Mentee Email',
        'Mentor Email',
        'Mentee Languages',
        'Mentor Languages',
    ];
}, 10, 2);
```

> Tip: Keep header names stable if you plan to import the CSV elsewhere (Sheets, Data Studio/Looker, etc.).

### B. Populate each row

Use the `mpro_matches_csv_row` filter to map **one match** (`$match`) into an **associative array** where the keys must match the headers you returned in step A.

```php
/**
 * Populate CSV rows for the "salem" client.
 */
add_filter('mpro_matches_csv_row', function(array $row, array $match, string $client_id){
    if ($client_id !== 'salem') {
        return $row;
    }

    // $match example keys:
    // ['mentee_id','mentor_id','mentee','mentor','score','percentage','field']

    $mentee_id = (int)($match['mentee_id'] ?? 0);
    $mentor_id = (int)($match['mentor_id'] ?? 0);

    // Pull any extra meta you want
    $mentee_email = get_post_meta($mentee_id, 'mpro_email', true);
    $mentor_email = get_post_meta($mentor_id, 'mpro_email', true);

    $mentee_langs = (array) get_post_meta($mentee_id, 'mpro_languages', true);
    $mentor_langs = (array) get_post_meta($mentor_id, 'mpro_languages', true);

    // Return row values keyed to your headers
    return [
        'Mentee Name'      => (string)($match['mentee'] ?? ''),
        'Mentee ID'        => $mentee_id,
        'Mentor Name'      => (string)($match['mentor'] ?? ''),
        'Mentor ID'        => $mentor_id,
        'Score %'          => (string)($match['percentage'] ?? ''),
        'Matched Fields'   => (string)($match['field'] ?? ''),
        'Mentee Email'     => $mentee_email,
        'Mentor Email'     => $mentor_email,
        'Mentee Languages' => implode(', ', $mentee_langs),
        'Mentor Languages' => implode(', ', $mentor_langs),
    ];
}, 10, 3);
```

### C. Include unmatched lists (optional)

You can also export the **Unmatched Mentees** and **Mentors Without Mentees** to separate CSVs by hooking into your download handler or adding buttons. If your handler provides arrays like `$unmatched_mentees`/`$unmatched_mentors`, you can stream them with your own headers (e.g., `Name`, `Reason`).

### D. Safe defaults & per‑client overrides

- If you **don’t** add these filters, the plugin uses a sensible default set of columns (Mentee, Mentor, Score %, Matched Fields).
- You can scope filters by `$client_id` to customize per program without affecting others.
- Avoid expensive lookups in `mpro_matches_csv_row`—prefer fetching only the meta you actually need.

### E. Troubleshooting

- **Headers misaligned:** Make sure the **keys** you return in `mpro_matches_csv_row` exactly match the **header strings** from `mpro_matches_csv_columns`.
- **Empty values:** Verify the post type and meta keys (`get_post_meta($mentee_id, 'key', true)`) exist for the client’s schema.
- **Encoding issues:** Wrap data with `(string)` and avoid raw arrays—use `implode(', ', $arr)`.
- **Permissions:** If download fails when logged out, confirm your CSV handler allows public access (nonce disabled or conditionally bypassed).

> **Where to put this code:** a site‑specific plugin or your theme’s `functions.php`, or (preferably) a small `mu-plugin` so the overrides load early and don’t disappear with a theme change.
