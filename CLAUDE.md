# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

MPro Matching is a WordPress plugin that matches mentors and mentees based on Gravity Forms survey responses. It uses a configurable scoring system to evaluate compatibility across multiple traits (career interests, skills, hobbies, personality, demographics) and generates optimized matches for multiple client programs.

## Plugin Architecture

### Core Components

**Main Plugin File:** `mpro-matching.php`
- Registers Gravity Forms hooks for submission processing
- Initializes client-specific handlers and display classes
- Creates admin menu structure with redirects to frontend matching pages

**Matching Engine:** Client-specific matching classes extend `Matching_Base`
- `class-matching-base.php` - Base class with shared functionality
- `class-leap4ed-matching.php` - Leap4Ed Community Health Program matching logic
- `class-salem-matching.php` - Salem State University matching logic
- Each client defines trait settings with caps, scoring, and bonuses

**Data Processing:**
- `class-gravity-forms.php` - Processes form submissions, creates `mentor_submission` posts with metadata
- `class-gravity-forms-SCHEMA.php` - Field mapping schemas
- `matching-schemas.php` - Client-specific configuration (max mentees per mentor, field mappings)

**Display & Export:**
- `class-mpro-display.php` - Renders matching reports via `[mentor_matches client_id="..."]` shortcode
- `class-matching-functions.php` - Utility functions for scoring, rebalancing, CSV export

**Admin:**
- `admin-reports.php` - Admin reporting functionality
- `admin-columns.php` - Custom columns for post lists
- `upload-data.php` - Bulk import tool

### Data Model

**Custom Post Type:** `mentor_submission`
- Each submission creates one post with `post_title` = full name
- Role stored as `mpro_role` meta: `1` = mentee, `2` = mentor
- `assigned_client` meta links to client ID (e.g., `leap4ed-chp`, `salem`)
- All form data saved as post meta with `mpro_` prefix

**Key Meta Fields:**
- Demographics: `mpro_gender`, `mpro_race`, `mpro_age`, `mpro_languages`
- Preferences: `mpro_match_pref` (ranked trait priorities), `mpro_interests`
- Career/Skills: `mpro_mentor_career_have`, `mpro_mentee_career_want`, `mpro_mentor_skills_have`, `mpro_mentee_skills_want`
- Personality: `mpro_trait_extraversion`, `mpro_trait_agreeableness`, `mpro_trait_conscientiousness`, `mpro_trait_stability`, `mpro_trait_openness` (TIPI scores)
- Capacity: `max_matches_per_mentor` (optional, overrides client default)

## Matching Algorithm

### Flow

1. Query all mentees and mentors for a client from database
2. For each mentee, score against all available mentors
3. Calculate trait-by-trait scores using client-specific settings
4. Require language overlap (hard constraint)
5. Collect all potential matches with scores
6. Sort by score descending, assign greedily respecting mentor capacity
7. Rebalance to honor per-mentor caps
8. Return matches, unmatched mentees, unmatched mentors

### Scoring System

Each trait has settings defined in `get_all_trait_settings()`:
- `cap` - Maximum points for this trait
- `base_per_match` - Base points per matching item
- `bonus_per_match` - Bonus multiplier for ranked preferences
- `bonus_eligible` - Whether ranking bonuses apply
- `description` - Display label

**Ranking Bonuses:** When mentor/mentee rank a trait in their top 5 preferences (`mpro_match_pref`), bonus points apply based on rank position (1st = 5x weight, 5th = 1x weight).

**Language Matching:** No shared language = ineligible match. 1 non-English language = 4 pts, 2+ languages = 4-8 pts.

**Personality (TIPI):** For Leap4Ed, scores based on whether mentor scores >= mentee on each trait.

**Percentage Score:** Each match shows percentage relative to the highest score in that cohort (not theoretical max).

### Capacity Management

- Default capacity per client set in `matching-schemas.php` (`max_mentees_per_mentor`)
- Per-mentor override: set `max_matches_per_mentor` post meta (0 = skip mentor, blank = use default)
- Mentees can also be skipped if `max_matches_per_mentor` = 0
- `rebalance_matches_by_caps()` trims lowest-score matches when mentor exceeds capacity

## Adding a New Client

1. **Create matching class** in `includes/class-{client}-matching.php` extending `Matching_Base`
   - Override `get_all_trait_settings()` with client-specific traits
   - Override `get_report_fields()` for data export columns
   - Implement `generate_matching_report()` or reuse base logic

2. **Add schema** in `matching-schemas.php`:
   ```php
   'client-slug' => [
       'max_mentees_per_mentor' => 2,
       'single_meta_fields' => [...],
       'multi_meta_fields' => [...],
       'field_map' => [...],
   ]
   ```

3. **Register in shortcode switch** in `class-mpro-display.php`:
   ```php
   case 'client-slug':
       require_once plugin_dir_path(__FILE__) . 'class-{client}-matching.php';
       $matching = new Client_Matching($client_id);
       break;
   ```

4. **Map Gravity Form** in `class-gravity-forms.php` - add form ID to `get_client_id_for_form()` and update field mappings if needed

5. **Add form submission hook** in `mpro-matching.php`:
   ```php
   add_action('gform_after_submission_{FORM_ID}', function($entry, $form) {
       $handler = new Leap4Ed_GravityForms();
       $handler->save_survey_data($entry, $form);
   }, 10, 2);
   ```

6. **Create admin menu entry** in `mpro-matching.php` - add submenu redirects for application form and matching report

## Development Commands

This is a WordPress plugin - no build/test commands. Development workflow:

1. Install plugin in WordPress environment
2. Activate from Plugins menu
3. Configure Gravity Forms (IDs must match hooks in `mpro-matching.php`)
4. Test by submitting forms and viewing matching reports at shortcode pages

**Testing Matches:** Add shortcode to a WordPress page:
```
[mentor_matches client_id="leap4ed-chp"]
```

**Debugging:** Enable WordPress debug mode and check error logs:
```php
// In wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

## Key Functions

**Scoring:**
- `score_trait_match()` - Base scoring with ranking weights
- `apply_trait_bonus()` - Calculate ranked preference bonuses
- `score_top3_trait_match()` - Match top 3 ranked items and apply bonuses
- `compare_top_3()` - Find overlapping items in ranked lists

**Data Access:**
- `mpro_get_single_meta_values()` - Fetch scalar post meta fields
- `mpro_get_multi_meta_values()` - Fetch array/comma-separated meta fields
- `mpro_get_mentor_cap()` - Resolve mentor capacity with fallback
- `mpro_get_program_counts()` - Count mentors/mentees per client

**Matching:**
- `generate_matching_report()` - Main algorithm (in client classes)
- `rebalance_matches_by_caps()` - Enforce per-mentor capacity limits
- `mpro_download_matches_csv()` - Export matches to CSV

**Utilities:**
- `normalize_string()` - Standardize text for comparison
- `smart_parse_ranked_choices()` - Parse comma-separated ranked fields with embedded commas
- `calculate_tipi_trait()` - Compute TIPI personality scores

## Important Notes

- Language matching is a hard requirement - no match without shared language
- Scores are cohort-relative (% of best match) not absolute
- TIPI personality matching only used for `leap4ed-chp` client
- CSV export customizable via `mpro_matches_csv_columns` and `mpro_matches_csv_row` filters
- Admin menu items redirect to frontend pages (no true admin pages)
- All form data processing happens in Gravity Forms after_submission hooks
