# CLAUDE.md - RO Mockup / Calendar Widgets

This file provides guidance to Claude Code when working with the calendar widgets and mockups.

## Project Overview

**ro-mockup** is a collection of HTML mockups and widgets for the Registrar's Office website. The main active component is the **calendars widget** which displays academic calendars fetched from the RODS PostgREST API.

**Tech Stack:**
- Pure HTML/CSS/JavaScript (no build step)
- PostgREST API at `https://apps8.reg.uga.edu/rods_api`
- Embedded in WordPress via iframe or direct inclusion

## Key Files

- `calendars.html` - Main calendar widget with multiple calendar views
- `next-event-widget.html` - Shows next upcoming event
- `featured-events-widget.html` - Featured events display
- `next-event-shortcode.php` - WordPress shortcode for next event

## Calendars Widget (`calendars.html`)

### Features
- **Multiple calendar types**: Academic, Key Dates, Parts of Term, Scheduling, Registration, Payment, Exam, Faculty Dates, Master Calendar
- **Two view modes for Parts of Term**:
  - Sequential (chronological list)
  - Grid (matrix with POT codes as rows, event types as columns)
- **Filter bar**: Click badges to filter by Part of Term codes
- **Responsive design**: Works on mobile and desktop

### Calendar Types (dropdown options)
| Widget Value | Database calendar_id | Description |
|--------------|---------------------|-------------|
| academic | Academic | Main academic calendar |
| keyDates | KeyDates | Important student dates |
| term | PartsOfTerm | Program-specific dates |
| scheduling | Scheduling | Banner scheduling periods |
| registration | Registration | Registration windows |
| payment | Payment | Payment deadlines |
| exam | Exam | Exam schedules |
| facultyDates | FacultyDates | Faculty-specific dates |
| master | (all) | Combined view of all calendars |

### Parts of Term Codes
Defined in `PART_OF_TERM_TAG_COLORS` and `PART_OF_TERM_LABELS`:
- **1** - Full Term
- **S1, S2** - 1st/2nd Short Session (FMBA, FYOS, Online BBA)
- **E, M** - Extended, May Session
- **LW** - Law
- **VM, VM2, VM3** - Vet Med, VM Core, VM Elective
- **VC1-VC9, V10-V17** - Vet Med Clinical Blocks
- **P1, P2** - Pharmacy, Pharmacy 10wk
- **PC1-PC4** - Pharmacy Clinical Rotations
- **EM1-EM3** - EMBA Terms
- **PM1-PM2** - PMBA Terms

### Session Codes
Summer terms have multiple sessions displayed separately:
- **MAIN** - Main Session
- **EXT** - Extended Session
- **MAY** - May Session
- **SSI, SSII** - Short Sessions I & II

Session labels display as: `SUMMER 2026 — Main Session` (em dash format)

### View Modes (Parts of Term only)
Toggle buttons in the filter bar:
- **Sequential** (list icon) - Events listed chronologically with POT badges
- **Grid** (grid icon) - Matrix view with:
  - Rows = POT codes with colored badges
  - Columns = Classes Begin, Drop/Add Ends, Census Date, Withdrawal Deadline, Classes End, Finals End
  - Cells = Dates

### Filter Bar
- Click **ALL** to show all events
- Click a specific POT code to show only that code
- Click additional codes to add them to selection
- Filters apply to both sequential and grid views

### API Integration
```javascript
const API_BASE = 'https://apps8.reg.uga.edu/rods_api';
// Fetches from /calendars table with filters
```

### Key JavaScript Functions
- `loadCalendarsFromAPI(calendarId)` - Fetches calendar data
- `renderCalendar(year, term)` - Renders sequential view
- `renderPartsOfTermGrid(year)` - Renders grid view
- `renderGridView(events, termLabel, termYear)` - Creates grid table
- `buildPotFilterBar()` - Builds filter badge bar
- `filterByPot(events)` - Applies POT filter to events

### Styling
All CSS is embedded in `<style>` tags within the HTML file:
- UGA brand colors (red: #BA0C2F, black: #000000)
- Merriweather font for headers
- Colored badges for calendar types and POT codes
- Responsive table layouts

## Development

### Local Testing
Open `calendars.html` directly in browser - it fetches from the live API.

### CORS Note
The API must have CORS headers configured in nginx to allow requests from any origin:
```nginx
add_header Access-Control-Allow-Origin "*" always;
```

### Deployment
Files are typically copied to WordPress theme or uploaded as static assets.

## Related Projects

- **rods-admin** - Admin GUI for managing calendar data in the database
- **RODS API** - PostgREST API serving the calendar data

---

Last updated: 2025-12-15
