<?php
/**
 * Next Event Shortcode for WordPress
 *
 * Usage:
 *   [next_event type="grades-due"]                    - Inline leaf
 *   [next_event type="grades-due" style="card"]       - Full card with title
 *   [next_event type="classes-begin" session="FALL"] - Filter by session
 *
 * Add this file to your theme's functions.php or as a custom plugin.
 */

// Enqueue required styles and scripts
function next_event_enqueue_assets() {
    // Only load on pages that might have the shortcode
    wp_enqueue_style(
        'next-event-widget',
        get_template_directory_uri() . '/css/next-event-widget.css',
        array(),
        '1.0.0'
    );

    // Add inline styles if CSS file doesn't exist
    wp_add_inline_style('next-event-widget', next_event_get_inline_styles());
}
add_action('wp_enqueue_scripts', 'next_event_enqueue_assets');

// Inline styles (can be moved to a CSS file)
function next_event_get_inline_styles() {
    return '
    /* Calendar Leaf - Tear-off style */
    .calendar-leaf {
        display: inline-flex;
        flex-direction: column;
        width: 50px;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
        background: white;
        margin: 0 6px;
        vertical-align: middle;
        cursor: default;
        user-select: none;
    }

    .calendar-leaf-month {
        background-color: #ba0c2f;
        color: white;
        font-size: 0.625rem;
        font-weight: 700;
        text-align: center;
        padding: 2px 0;
        letter-spacing: 0.1em;
        font-family: "Oswald", sans-serif;
        text-transform: uppercase;
    }

    .calendar-leaf-body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 6px;
        background: white;
        border-top: 1px solid #f3f4f6;
    }

    .calendar-leaf-day {
        font-size: 1.25rem;
        font-weight: 700;
        color: #1f2937;
        line-height: 1;
        font-family: "Oswald", sans-serif;
    }

    .calendar-leaf-weekday {
        font-size: 0.5rem;
        color: #9ca3af;
        font-weight: 500;
        text-transform: uppercase;
        margin-top: 2px;
        font-family: "Oswald", sans-serif;
        letter-spacing: 0.05em;
    }

    /* Card style - larger leaf */
    .calendar-event-card .calendar-leaf {
        width: 70px;
    }

    .calendar-event-card .calendar-leaf-month {
        font-size: 0.75rem;
        padding: 4px 0;
    }

    .calendar-event-card .calendar-leaf-body {
        padding: 10px;
    }

    .calendar-event-card .calendar-leaf-day {
        font-size: 1.75rem;
    }

    .calendar-event-card .calendar-leaf-weekday {
        font-size: 0.625rem;
        margin-top: 4px;
    }

    /* Event Card Container */
    .calendar-event-card {
        display: flex;
        align-items: center;
        padding: 20px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        max-width: 600px;
        margin: 24px 0;
        transition: box-shadow 0.3s ease;
    }

    .calendar-event-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .calendar-event-card .leaf-wrapper {
        flex-shrink: 0;
    }

    .calendar-event-card .content-wrapper {
        margin-left: 16px;
        flex: 1;
        border-left: 2px solid #f3f4f6;
        padding-left: 24px;
    }

    .calendar-event-card .event-title {
        font-weight: 700;
        color: #111827;
        font-size: 1.25rem;
        line-height: 1.3;
        font-family: "Merriweather", serif;
        margin: 0;
    }

    .calendar-event-card .event-subtext {
        color: #6b7280;
        font-size: 1rem;
        margin-top: 8px;
        font-family: "Merriweather", serif;
        font-style: italic;
    }

    .inline-date {
        display: inline-flex;
        align-items: center;
    }
    ';
}

/**
 * Main shortcode handler
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output
 *
 * Usage:
 *   [next_event type="grades-due"]                           - Card with term prefix, no subtext
 *   [next_event type="grades-due" subtext=""]                - Explicitly no subtext
 *   [next_event type="grades-due" subtext="Custom message"]  - Custom subtext
 *   [next_event type="grades-due" session="FALL"]            - Filter by session
 *   [next_event type="grades-due" title="Custom Title"]      - Custom title
 *   [next_event type="grades-due" show_term="false"]         - Hide term prefix
 */
function next_event_shortcode($atts) {
    $atts = shortcode_atts(array(
        'type'      => '',           // Required: event_type slug (e.g., "grades-due")
        'style'     => 'card',       // "inline" or "card" (default: card)
        'session'   => '',           // Optional: filter by session code (FALL, SPRING, SSI, etc.)
        'calendar'  => '',           // Optional: filter by calendar_id
        'title'     => null,         // Optional: custom title (null = use API title with term)
        'subtext'   => null,         // Optional: custom subtext (null = use description, '' = hide)
        'show_term' => 'true',       // Optional: show term prefix in auto-title
    ), $atts, 'next_event');

    $event_type = sanitize_text_field($atts['type']);
    $style = sanitize_text_field($atts['style']);
    $session = sanitize_text_field($atts['session']);
    $calendar = sanitize_text_field($atts['calendar']);
    $custom_title = $atts['title'];
    $custom_subtext = $atts['subtext'];
    $show_term = $atts['show_term'] !== 'false';

    if (empty($event_type)) {
        return '<!-- next_event: missing type parameter -->';
    }

    // Fetch from API
    $event = next_event_fetch($event_type, $session, $calendar);

    if (!$event) {
        return '<span class="next-event-error">[date unavailable]</span>';
    }

    // Build options
    $options = array(
        'title'     => $custom_title,
        'subtext'   => $custom_subtext,
        'show_term' => $show_term,
    );

    // Render based on style
    if ($style === 'card') {
        return next_event_render_card($event, $options);
    } else {
        return next_event_render_inline($event);
    }
}
add_shortcode('next_event', 'next_event_shortcode');

/**
 * Fetch event from RODS API
 */
function next_event_fetch($event_type, $session = '', $calendar = '') {
    $api_base = 'https://apps8.reg.uga.edu/rods_api';

    // Use RPC function if no extra filters
    if (empty($session) && empty($calendar)) {
        $url = $api_base . '/rpc/next_event?p_event_type=' . urlencode($event_type);
    } elseif (!empty($calendar)) {
        $url = $api_base . '/rpc/next_event?p_event_type=' . urlencode($event_type)
             . '&p_calendar_id=' . urlencode($calendar);
    } else {
        // Direct query with session filter
        $url = $api_base . '/calendars?event_type=eq.' . urlencode($event_type)
             . '&session_code=like.*' . urlencode($session) . '*'
             . '&start_iso=gte.now'
             . '&order=start_iso.asc'
             . '&limit=1';
    }

    // Cache for 5 minutes
    $cache_key = 'next_event_' . md5($url);
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    $response = wp_remote_get($url, array(
        'timeout' => 10,
        'headers' => array('Accept' => 'application/json'),
    ));

    if (is_wp_error($response)) {
        error_log('next_event API error: ' . $response->get_error_message());
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($data) || !is_array($data)) {
        return null;
    }

    $event = is_array($data[0]) ? $data[0] : $data;

    // Cache result
    set_transient($cache_key, $event, 5 * MINUTE_IN_SECONDS);

    return $event;
}

/**
 * Render inline calendar leaf
 */
function next_event_render_inline($event) {
    $date_parts = next_event_parse_date($event['start_iso']);

    $html = '<span class="inline-date">';
    $html .= '<span class="calendar-leaf">';
    $html .= '<div class="calendar-leaf-month">' . esc_html($date_parts['month']) . '</div>';
    $html .= '<div class="calendar-leaf-body">';
    $html .= '<span class="calendar-leaf-day">' . esc_html($date_parts['day']) . '</span>';
    $html .= '<span class="calendar-leaf-weekday">' . esc_html($date_parts['weekday']) . '</span>';
    $html .= '</div>';
    $html .= '</span>';
    $html .= '</span>';

    return $html;
}

/**
 * Render full event card
 *
 * @param array $event Event data from API
 * @param array $options Display options:
 *   - title: custom title (null = use API title with term prefix)
 *   - subtext: custom subtext (null = use description, '' = hide)
 *   - show_term: whether to prefix title with term name (default true)
 */
function next_event_render_card($event, $options = array()) {
    $date_parts = next_event_parse_date($event['start_iso']);
    $term_name = next_event_get_term_name($event['term_code']);

    // Build title
    if (isset($options['title']) && $options['title'] !== null) {
        $display_title = esc_html($options['title']);
    } elseif (!isset($options['show_term']) || $options['show_term'] !== false) {
        $display_title = esc_html($term_name . ' ' . $event['title']);
    } else {
        $display_title = esc_html($event['title']);
    }

    // Build subtext
    $show_subtext = true;
    $display_subtext = '';
    if (isset($options['subtext'])) {
        if ($options['subtext'] === '' || $options['subtext'] === null) {
            $show_subtext = false;
        } else {
            $display_subtext = esc_html($options['subtext']);
        }
    } elseif (!empty($event['description'])) {
        $display_subtext = esc_html($event['description']);
    } else {
        $show_subtext = false;
    }

    $html = '<div class="calendar-event-card">';
    $html .= '<div class="leaf-wrapper">';
    $html .= '<span class="calendar-leaf">';
    $html .= '<div class="calendar-leaf-month">' . esc_html($date_parts['month']) . '</div>';
    $html .= '<div class="calendar-leaf-body">';
    $html .= '<span class="calendar-leaf-day">' . esc_html($date_parts['day']) . '</span>';
    $html .= '<span class="calendar-leaf-weekday">' . esc_html($date_parts['weekday']) . '</span>';
    $html .= '</div>';
    $html .= '</span>';
    $html .= '</div>';
    $html .= '<div class="content-wrapper">';
    $html .= '<h3 class="event-title">' . $display_title . '</h3>';
    if ($show_subtext) {
        $html .= '<p class="event-subtext">' . $display_subtext . '</p>';
    }
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

/**
 * Parse ISO date string to display parts
 */
function next_event_parse_date($iso_date) {
    if (empty($iso_date)) {
        return array('month' => 'ERR', 'day' => '--', 'weekday' => '---');
    }

    // Normalize timezone offset (add missing colon)
    $normalized = preg_replace('/([+-]\d{2})$/', '$1:00', $iso_date);

    $timestamp = strtotime($normalized);
    if ($timestamp === false) {
        return array('month' => 'ERR', 'day' => '--', 'weekday' => '---');
    }

    return array(
        'month'   => strtoupper(date('M', $timestamp)),
        'day'     => date('j', $timestamp),
        'weekday' => date('D', $timestamp),
    );
}

/**
 * Get term display name from term code
 */
function next_event_get_term_name($term_code) {
    if (empty($term_code) || strlen($term_code) < 6) {
        return '';
    }

    $year = substr($term_code, 0, 4);
    $term_num = substr($term_code, 4, 2);

    switch ($term_num) {
        case '08': return "Fall $year";
        case '02': return "Spring $year";
        case '05': return "Summer $year";
        default:   return $year;
    }
}

/**
 * Multiple events shortcode
 * Usage: [next_events types="classes-begin,withdrawal-deadline,final-exams"]
 */
function next_events_shortcode($atts) {
    $atts = shortcode_atts(array(
        'types'   => '',
        'style'   => 'card',
        'session' => '',
    ), $atts, 'next_events');

    $types = array_map('trim', explode(',', $atts['types']));
    $style = $atts['style'];
    $session = $atts['session'];

    if (empty($types)) {
        return '<!-- next_events: missing types parameter -->';
    }

    $html = '<div class="next-events-list">';

    foreach ($types as $type) {
        $event = next_event_fetch($type, $session);
        if ($event) {
            if ($style === 'card') {
                $html .= next_event_render_card($event);
            } else {
                $html .= next_event_render_inline($event);
            }
        }
    }

    $html .= '</div>';

    return $html;
}
add_shortcode('next_events', 'next_events_shortcode');
