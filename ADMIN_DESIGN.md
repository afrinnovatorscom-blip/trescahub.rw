# Tresca Hub — Admin Panel Design System
**Document:** `design.md`  
**Product:** Tresca Hub  
**Module:** Administrative Dashboard and Marketplace Management  
**Primary View:** All Services  
**Environment:** Laravel Blade  
**Design Direction:** Modern, professional, enterprise-grade administration interface

---

## 1. Design Overview

### 1.1 Purpose

This document defines the visual standards, layout architecture, navigation structure, component behavior, and responsive design requirements for the Tresca Hub administration panel.

The goal is to deliver a consistent, accessible, and professional interface for managing services, backgrounds, categories, products, partners, portfolio projects, customer requests, vendors, orders, inventory, and marketplace operations.

The redesign must improve visual clarity and usability without breaking existing backend logic, routes, permissions, database relationships, or business workflows.

### 1.2 Design Principles

1. **Clarity first:** Every page must have a clear title, purpose, and primary action.
2. **Consistency:** Shared elements must maintain the same appearance and behavior across administrative modules.
3. **Efficient workflows:** Common operations should require minimal navigation and unnecessary clicks.
4. **Information hierarchy:** Important information and actions must be immediately recognizable.
5. **Responsive behavior:** The interface must work on desktop, tablet, and mobile screens.
6. **Accessible interaction:** Controls must have clear labels, visible focus states, and sufficient contrast.
7. **Functional integrity:** Existing application functionality must be preserved during visual improvements.

---

## 2. Global Visual Design System

### 2.1 Color Palette

Use a restrained palette with deep navy navigation, a white working surface, subtle neutral backgrounds, and blue primary actions. Turquoise should be used sparingly for brand accents.

| Token | Color | Application |
|---|---|---|
| Primary | `#2563EB` | Primary buttons, active links, focus indicators |
| Primary Hover | `#1D4ED8` | Hover state for primary actions |
| Sidebar | `#0F172A` | Main navigation background |
| Sidebar Secondary | `#111C31` | Nested navigation and subtle sidebar sections |
| Brand Accent | `#2DD4BF` | Selected brand details and limited highlights |
| Page Background | `#F8FAFC` | Main application background |
| Surface | `#FFFFFF` | Cards, tables, dropdowns, dialogs |
| Border | `#E2E8F0` | Dividers, card borders, input outlines |
| Heading Text | `#0F172A` | Page titles and primary labels |
| Body Text | `#475569` | Descriptions and secondary information |
| Muted Text | `#64748B` | Helper text and metadata |
| Success | `#15803D` | Successful operations and active status |
| Warning | `#B45309` | Pending actions and warnings |
| Danger | `#B91C1C` | Destructive actions and errors |
| Info | `#0369A1` | Informational messages |

Use semantic status colors consistently. Do not use color as the only means of communicating a status.

### 2.2 Typography

Use **Inter** as the preferred interface font, with a system sans-serif fallback.

```css
font-family: Inter, ui-sans-serif, system-ui, -apple-system,
             BlinkMacSystemFont, "Segoe UI", sans-serif;
```

Recommended typography scale:

| Element | Size | Weight |
|---|---:|---:|
| Page title | 24–28px | 700 |
| Section title | 18–20px | 600 |
| Card heading | 15–16px | 600 |
| Body text | 14px | 400 |
| Table content | 13–14px | 400 |
| Table headings | 12px | 600 |
| Supporting labels | 12–13px | 500 |
| Button text | 13–14px | 600 |

Use sentence case for page titles, navigation labels, and buttons. Avoid unnecessary uppercase text except for small, restrained group labels.

### 2.3 Spacing and Dimensions

Use a consistent spacing scale based on 4px increments.

- Compact spacing: 4–8px.
- Standard component spacing: 12–16px.
- Card padding: 20–24px.
- Section spacing: 24–32px.
- Main content horizontal padding: 24–32px on desktop.
- Sidebar width: approximately 250–264px when expanded.
- Collapsed sidebar width: approximately 72px.
- Top header height: approximately 64–72px.
- Standard input and button height: 40–44px.
- Standard border radius: 8–12px.

Avoid excessive empty space, oversized controls, and decorative elements that compete with the working content.

### 2.4 Surface and Elevation

Use white surfaces with subtle borders as the primary container style.

- Cards: white background, 1px neutral border, 10–12px radius.
- Dropdowns and menus: white background with a subtle shadow.
- Dialogs: white background, clear title and footer actions.
- Hover states: a light neutral background or a restrained primary tint.
- Focus states: a clearly visible primary-colored outline.

Avoid heavy shadows, excessive gradients, glass effects, and unnecessary animation.

---

## 3. Application Layout Architecture

The administrative interface must use a shared application shell consisting of:

1. Persistent sidebar navigation.
2. Top application header.
3. Breadcrumb navigation where useful.
4. Main page heading and contextual actions.
5. Main content area.
6. Optional contextual dialogs, drawers, and notifications.

### 3.1 Sidebar

The sidebar must use a deep navy background and remain visually distinct from the light main content area.

**Header area**
- Display the Tresca Hub logo or existing brand mark.
- Display the product name when the sidebar is expanded.
- Provide a collapse/expand control.
- Preserve logo proportions and avoid introducing an unrelated brand identity.

**Navigation area**
- Organize navigation into MAIN, MARKETPLACE, and SYSTEM groups.
- Use consistent icons from the project's existing icon library.
- Use clear active states, hover states, and keyboard focus indicators.
- Support expandable groups for modules containing multiple destinations.
- Preserve the current navigation hierarchy and route destinations.

**Footer area**
- Show the signed-in administrator's profile information where available.
- Provide access to profile options and logout.
- Keep footer controls anchored near the bottom of the sidebar.

### 3.2 Sidebar Navigation Hierarchy

#### MAIN

- Dashboard
- Services
  - All Services
  - Add Service
- Backgrounds
  - All Backgrounds
  - Add Background
- Categories
  - All Categories
  - Add Category
- Shop Categories
  - All Categories
  - Add Category
- Shop Products
  - All Products
  - Add Product
- Partners
  - All Partners
  - Add Partner
- Works
  - All Works
  - Add Work
- CTA Categories
  - All CTA Categories
  - Add CTA Category
- CTAs
  - All CTAs
  - Add CTA
- Testimonials
- Service Requests

#### MARKETPLACE

- Catalog
- Product Approvals
- Vendors
- Product Requests
- Orders
- Deliveries
- Inventory
- Reports
- Audit Log
- Users
- Roles
- Notifications

#### SYSTEM

- Settings
- Logout

Use concise labels and distinguish similar modules through their parent groups or descriptive subtitles. Avoid duplicating a parent navigation entry when its expanded submenu already provides the same destination.

The navigation must reflect actual application routes and administrator permissions. Do not introduce new routes or expose restricted modules solely to match this document.

### 3.3 Sidebar Interaction

- Highlight the current page and its parent navigation group.
- Automatically expand the group containing the active page.
- Preserve expanded/collapsed preferences where practical.
- Provide tooltips for icon-only navigation when collapsed.
- On mobile, display the sidebar as an off-canvas drawer with a visible close control.
- Keep keyboard navigation functional.
- Use a short, subtle transition for expansion and collapse.

### 3.4 Top Application Header

The header should provide global navigation utilities without competing with the page title.

**Left section**
- Sidebar toggle.
- Optional breadcrumb trail, such as `Dashboard / Services / All Services`.

**Center or flexible section**
- Global search field with the placeholder `Search anything...`.
- Search should remain usable on smaller screens without crowding other controls.

**Right section**
- Existing quick feedback or support action.
- Notifications shortcut, if supported by the application.
- Administrator avatar or initials.
- User name and role where space permits.
- Profile dropdown containing existing account actions.

Do not display decorative controls that have no implemented action. All visible buttons, search fields, dropdowns, and shortcuts must work as expected.

---

## 4. Main Content Design Standards

Every administrative page should follow a consistent content structure.

### 4.1 Page Heading

Each page heading must include:

- A descriptive page title.
- A short contextual description when it improves understanding.
- A primary action aligned to the right on desktop.
- Optional breadcrumbs above the title.

Example:

**All Services**

Manage the services available across the Tresca Hub platform.

Primary action: **+ New Service**

On mobile, stack the heading, description, and primary action vertically when horizontal space is insufficient.

### 4.2 Content Containers

Use a consistent pattern for tables, forms, dashboards, and reports.

- White surface against the light page background.
- Neutral border and restrained radius.
- Clear internal padding.
- Consistent section headers.
- Responsive overflow behavior for dense content.
- Loading, empty, error, and populated states.

Avoid placing every small element inside a separate card. Group related content logically to maintain visual simplicity.

### 4.3 Buttons

**Primary button**
- Blue background with white text.
- Used for creating, saving, submitting, or confirming the main action.

**Secondary button**
- White or neutral background with a subtle border.
- Used for canceling, filtering, or secondary navigation.

**Tertiary button**
- Text or icon button for low-emphasis actions.

**Destructive button**
- Red styling for irreversible or dangerous operations.
- Confirmation required for consequential deletions.

Buttons must include appropriate disabled, loading, hover, and keyboard focus states.

### 4.4 Forms

Forms should use consistent field layouts and validation.

- Place labels above inputs.
- Show required fields clearly.
- Use concise helper text when necessary.
- Group related fields into logical sections.
- Display validation errors beside the relevant field.
- Preserve entered values when validation fails.
- Provide clear Save and Cancel actions.
- Use confirmation prompts only when they prevent meaningful mistakes.

Long forms should be divided into sections with clear headings rather than one uninterrupted column of fields.

### 4.5 Tables

All listing pages should share a reusable table design.

- White table surface.
- Light neutral row separators.
- Clear column headings.
- Consistent row heights and alignment.
- Restrained hover highlighting.
- Status badges with semantic colors.
- Right-aligned numeric values where appropriate.
- Action controls grouped at the end of each row.
- Responsive horizontal scrolling or an alternative compact presentation on narrow screens.

Where applicable, support search, filters, sorting, pagination, result counts, and bulk actions. Implement these features only when supported by the existing backend or explicitly included in the development scope.

### 4.6 Status Badges

Use compact, readable badges rather than large colored blocks.

Examples include:

- Active: green.
- Inactive: neutral gray.
- Pending: amber.
- Approved: green.
- Rejected: red.
- Processing: blue.
- Failed: red.

Status labels must use the actual status values returned by the application.

### 4.7 Feedback and Notifications

- Success messages should confirm completed actions.
- Validation errors should explain what needs correction.
- General errors should provide a useful next step when possible.
- Notifications should not obscure important controls.
- Toast messages should be dismissible where appropriate.
- Loading indicators should prevent duplicate submissions during ongoing operations.

---

## 5. Primary Page: All Services

**Route:** `/admin/services`  
**Creation route:** `/admin/services/create`

### 5.1 Page Objective

Provide administrators with an efficient interface for viewing, searching, and managing the platform's service records.

### 5.2 Page Structure

The page should follow this hierarchy:

1. Breadcrumb navigation.
2. Page title and supporting description.
3. Primary New Service button.
4. Optional search and filtering controls.
5. Service listing table.
6. Pagination and result summary when applicable.

### 5.3 Page Heading

Title: **All Services**

Description: `Manage service listings, pricing, categories, and availability.`

Primary action: **+ New Service**

The primary action should navigate to the existing service creation route.

### 5.4 Table Specification

Use the following column order to preserve the existing information architecture:

| Column | Presentation |
|---|---|
| # | Row number or record identifier |
| Thumb | Service thumbnail with consistent dimensions |
| Title | Service title with readable truncation where necessary |
| Price | Formatted price using the existing currency rules |
| Classification | Associated classification |
| Subcategories | Related subcategory labels or a compact count |
| Working Day | Existing working-day value or schedule |
| Status | Semantic status badge |
| Actions | Available record operations |

**Thumbnail requirements**
- Use a consistent square preview, approximately 44–48px.
- Apply an 8px corner radius.
- Use `object-fit: cover` for image consistency.
- Display a neutral placeholder when an image is unavailable.
- Provide useful alternative text where appropriate.

**Title requirements**
- Use medium font weight.
- Allow long titles to truncate without expanding the entire table.
- Provide access to the full title when it is truncated.

**Price requirements**
- Apply the application's existing currency formatting.
- Avoid hardcoding a currency if the application supports a configurable currency.
- Align price values consistently.

**Classification and subcategories**
- Display readable text or restrained badges.
- Prevent long category lists from breaking the table layout.
- Preserve the underlying category relationships.

**Working Day**
- Display the existing schedule or working-day value clearly.
- Use a consistent representation for unavailable values.

**Status**
- Use compact badges.
- Preserve the actual service status and existing business rules.

**Actions**
- Include only operations supported by the application, such as View, Edit, Activate/Deactivate, or Delete.
- Use icon buttons with accessible labels when appropriate.
- Keep the action area consistent across all administrative tables.
- Require confirmation before destructive operations.

### 5.5 Table Toolbar

When supported by the existing implementation, place table controls above the listing.

- Search services by title or supported searchable fields.
- Filter by status or classification when relevant.
- Provide a Clear Filters action when filters are active.
- Display a result count.
- Keep the New Service button in the page heading rather than duplicating it unnecessarily.

On desktop, align the toolbar controls in one row where space permits. On mobile, allow the controls to wrap or stack.

### 5.6 Empty State

When there are no service records, show a deliberate empty state rather than an unexplained blank table.

**Heading:** No services found

**Description:** There are no services to display yet. Create a service to start building your catalog.

**Primary action:** Create your first service

Use a simple, existing icon-library illustration such as a package or grid icon. Keep the empty state centered within the content surface, with moderate spacing and a clear call to action.

If search or filters produce zero results, use a contextual message such as `No services match your filters.` Provide a Clear Filters action where applicable.

### 5.7 Loading and Error States

**Loading state**
- Preserve the table structure where possible.
- Use lightweight skeleton rows or a progress indicator.
- Avoid large layout shifts.

**Error state**
- Display a concise explanation that services could not be loaded.
- Provide a Retry action if the application can safely repeat the request.
- Avoid exposing internal exception messages or sensitive server details.

### 5.8 Pagination

When the number of services requires pagination, provide:

- Current page and total pages when available.
- Previous and Next controls.
- A consistent page-size selector if supported.
- A result summary such as `Showing 1–10 of 42 services`.

Pagination must remain synchronized with the backend query and existing request parameters.

---

## 6. Marketplace Administration

All marketplace pages must reuse the same application shell, typography, spacing, table components, form styles, and feedback patterns established in this design system.

### 6.1 Catalog and Product Approvals

- Provide clear listing titles and contextual descriptions.
- Display product thumbnails, names, vendors, categories, and relevant status information.
- Distinguish pending approval from published or rejected products.
- Provide review actions according to the administrator's permissions.
- Require confirmation for consequential approval or rejection operations where appropriate.

### 6.2 Vendors

- Display vendor identity, contact information where authorized, account status, and relevant activity.
- Use consistent status badges.
- Keep vendor details, editing actions, and management options logically grouped.
- Respect privacy and access-control requirements.

### 6.3 Product Requests

- Display request identifiers, requester information, request date, and current status where supported.
- Make request details accessible without overcrowding the listing.
- Use consistent workflow status indicators.
- Preserve existing request-processing behavior.

### 6.4 Orders and Deliveries

- Make order references and delivery status easy to scan.
- Present customer, fulfillment, and tracking information in clearly separated sections.
- Use consistent status labels and timestamps.
- Make available actions dependent on the current workflow state and user permissions.

### 6.5 Inventory

- Prioritize product identification, stock quantity, and stock status.
- Highlight low-stock or unavailable inventory using semantic indicators.
- Keep inventory adjustment actions clear and deliberate.
- Preserve existing inventory calculations and validation.

### 6.6 Reports

- Use consistent page headings and filter placement.
- Present key metrics in restrained summary cards when meaningful.
- Use readable charts and tables with labels and accessible alternatives.
- Provide export actions only where implemented.

### 6.7 Audit Log

- Prioritize timestamp, user, action, and affected resource.
- Support filtering where available.
- Maintain a readable, compact table.
- Do not expose sensitive information unnecessarily.

### 6.8 Users, Roles, and Notifications

- Clearly identify users, assigned roles, and account states.
- Make permission-related actions explicit.
- Avoid implying that a user has permissions that the backend does not grant.
- Use readable notification titles, timestamps, and read/unread states.
- Preserve the application's actual authorization model.

---

## 7. Other Administrative Modules

The following modules should follow the same shared visual patterns:

- Dashboard
- Backgrounds
- Categories
- Shop Categories
- Shop Products
- Partners
- Works
- CTA Categories
- CTAs
- Testimonials
- Service Requests
- Settings

Each module should have:

1. A descriptive page heading.
2. A consistent primary action, where applicable.
3. Reusable listing, form, and detail components.
4. Clear empty, loading, and error states.
5. Consistent validation and feedback.
6. Responsive behavior.
7. Existing route and permission compatibility.

Dashboard pages may use summary cards and charts when they provide actionable information. Content-management pages should prioritize efficient listing and editing workflows instead of unnecessary analytics.

---

## 8. Responsive Design Requirements

### Desktop: 1200px and above

- Expanded sidebar by default.
- Full header utilities.
- Page heading and primary action aligned horizontally.
- Full table columns where space permits.
- Comfortable content padding.

### Tablet: 768px–1199px

- Allow the sidebar to collapse.
- Reduce content padding to approximately 20px.
- Allow header utilities to condense.
- Preserve table readability through horizontal scrolling when needed.
- Prevent page headings and actions from overlapping.

### Mobile: below 768px

- Use an off-canvas sidebar.
- Provide a compact header and accessible menu toggle.
- Stack page titles and primary actions where necessary.
- Use responsive forms with full-width fields.
- Allow dense tables to scroll horizontally or use an appropriate compact layout.
- Keep important actions visible and easy to tap.
- Avoid horizontal overflow across the overall page.
- Maintain adequate touch targets, preferably around 44px.

Responsive behavior must be implemented and tested rather than relying exclusively on a desktop layout that scales down.

---

## 9. Accessibility and Interaction Standards

- Use semantic HTML elements and appropriate form labels.
- Ensure text and interactive controls have sufficient color contrast.
- Provide keyboard access to navigation, dialogs, dropdowns, and forms.
- Use visible focus indicators.
- Include accessible names for icon-only buttons.
- Associate validation errors with their corresponding inputs.
- Provide meaningful alternative text for informative images.
- Respect reduced-motion preferences.
- Avoid using color as the sole status indicator.
- Ensure dialogs manage focus correctly and can be dismissed appropriately.

Target WCAG 2.2 AA accessibility practices wherever practical.

---

## 10. Laravel Implementation Guidelines

The redesign must integrate with the existing Laravel application.

### 10.1 Shared Blade Layout

Use the existing administrative layout as the shared application shell where practical. Reuse shared Blade components or partials for:

- Sidebar navigation.
- Header and profile menu.
- Breadcrumbs.
- Page headings.
- Buttons.
- Tables.
- Status badges.
- Alerts and toast messages.
- Empty states.
- Confirmation dialogs.
- Form controls.

Avoid duplicating layout markup across individual administrative pages.

### 10.2 Styling

- Reuse the project's existing styling framework and build configuration.
- Centralize design tokens where the current architecture supports it.
- Avoid introducing a second CSS framework unnecessarily.
- Scope styles to prevent unintended effects on storefront pages.
- Keep responsive breakpoints consistent.
- Remove obsolete styles only after confirming they are no longer used.

### 10.3 Routes and Backend Compatibility

Preserve existing route names, URLs, controller responsibilities, middleware, policies, validation rules, and database relationships unless a separate functional change is explicitly requested.

Use Laravel route helpers in Blade templates where appropriate rather than hardcoding development URLs such as `http://127.0.0.1:2000`.

Do not assume that a route exists merely because it appears in a visual specification. Verify the actual route definitions before wiring up controls.

### 10.4 Permissions and Security

- Display actions according to the application's authorization rules.
- Continue enforcing permissions on the server.
- Do not treat hidden buttons as a security mechanism.
- Preserve CSRF protection for state-changing requests.
- Preserve appropriate HTTP methods and confirmation behavior.
- Do not expose secrets, tokens, or sensitive server data in the interface.

### 10.5 Performance

- Avoid unnecessary JavaScript dependencies.
- Reuse existing assets and icon libraries.
- Optimize thumbnail sizes.
- Avoid rendering unnecessarily large datasets on one page.
- Use the application's existing pagination and query patterns.
- Minimize layout shifts and expensive animations.

---

## 11. Design Acceptance Criteria

The implementation is complete when the following requirements are satisfied.

### Visual consistency
- [ ] Sidebar, header, typography, spacing, and buttons are consistent.
- [ ] The interface uses the defined navy, blue, turquoise, and neutral palette appropriately.
- [ ] Tables, forms, badges, and cards share reusable styles.
- [ ] The interface avoids clutter and unnecessary decoration.

### All Services page
- [ ] The page heading and New Service action are clear.
- [ ] All specified columns are displayed correctly.
- [ ] Thumbnails and long text remain visually consistent.
- [ ] Status and row actions use the correct application data.
- [ ] Empty, loading, and error states are handled.
- [ ] Search, filters, and pagination work if provided.

### Responsive behavior
- [ ] Sidebar behavior works on desktop and mobile.
- [ ] The page remains usable on tablet screens.
- [ ] Forms and controls fit narrow screens.
- [ ] Tables do not create unintended page-wide horizontal overflow.
- [ ] Important actions remain accessible.

### Functional integrity
- [ ] Existing Laravel routes continue to work.
- [ ] Forms retain server-side validation.
- [ ] User permissions are respected.
- [ ] Create, edit, view, and delete operations continue to behave correctly where supported.
- [ ] No decorative control is left nonfunctional.
- [ ] Existing storefront and marketplace workflows are not unintentionally broken.

### Final verification
- [ ] Test the All Services page with populated data.
- [ ] Test the empty state.
- [ ] Test validation and server errors.
- [ ] Test navigation and profile actions.
- [ ] Test the layout at desktop, tablet, and mobile widths.
- [ ] Check browser console errors and Laravel logs.

---

## 12. Final Design Direction

Tresca Hub should feel like a modern, dependable business administration platform: structured, fast to navigate, visually restrained, and easy to maintain.

The dark navy sidebar establishes the application's identity, while the light workspace keeps operational data readable. Blue provides a clear action hierarchy, turquoise adds a limited brand accent, and neutral surfaces support consistent tables, forms, and reports.

The primary objective is not simply to make the admin panel look different. It is to create a cohesive design system that improves usability across the entire administration experience while preserving the reliability and functionality of the existing Laravel application.
