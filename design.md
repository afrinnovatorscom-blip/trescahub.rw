# TRESCA Hub --- Frontend Design Reference (`design.md`)

> **Purpose:** Use this document as the reference when debugging the
> existing TRESCA Hub public frontend. Preserve current features and
> business rules. Fix broken layouts, routes, forms, and interactions
> before adding features or starting admin-panel work.
>
> **Live reference inspected:** https://trescahub.rw/\
> **Inspection date:** 2026-10-22\
> **Important limitation:** This document records the live site's
> observable content, page structure, and interaction expectations.
> Exact CSS tokens, font names, pixel measurements, breakpoints, and
> every interactive state must be verified against the local source code
> and browser; do not invent them.

## 1. Product and design goals

TRESCA Hub is a service coordination and ordering platform operated
under TRESCA Group. The public website combines:

-   Professional service packages from five specialist stores.
-   Physical products and digital services.
-   Requests for custom services or products.
-   Customer/partner information.
-   Ordering, checkout, and order tracking journeys.
-   WhatsApp contact/order actions where configured.

### Frontend priorities

1.  Keep the existing public routes and business functionality working.
2.  Make the site consistent, responsive, accessible, and easy to
    navigate.
3.  Ensure service and product information is readable and prices are
    clear.
4.  Keep calls to action obvious without creating competing primary
    buttons.
5.  Fix existing defects before introducing new design or features.
6.  Keep the public storefront separate from admin/vendor interfaces.

## 2. Global layout and shared components

### Header / primary navigation

The observed public navigation contains:

-   Home
-   Services
-   Shop
-   Request
-   Partners

The site also uses a brand identity with the TRESCA Hub name and
"Powered by TRESCA Group" messaging.

Requirements: - Use one consistent shared header across public pages. -
Each navigation item must point to its intended public route. - Show a
clear active state for the current page. - Ensure the header works on
narrow screens, including a usable mobile navigation menu. - Prevent the
header from overlapping page content or dialogs. - Keep keyboard focus
visible and all navigation links keyboard accessible.

### Main content container

-   Use a consistent centered content container and horizontal page
    padding.
-   Keep headings, cards, forms, and section edges aligned.
-   Avoid horizontal overflow at mobile and tablet widths.
-   Maintain a clear hierarchy between page title, introductory text,
    section headings, and content.
-   Use consistent vertical spacing between sections.
-   Avoid fixed heights that clip descriptions, validation messages, or
    translated/long content.

### Footer

The live footer contains: - TRESCA Hub name and "Your Link to Services"
tagline. - "Powered by TRESCA Group." - A short legal/business
clarification that TRESCA Hub is a coordination and ordering platform
under TRESCA Group. - Service links. - Quick links: Home, Shop, For
Partners, Request a Service, All Services. - Contact details and Kigali,
Rwanda location. - Copyright notice.

Requirements: - Reuse a shared footer on public pages. - Keep footer
links valid and avoid duplicate or stale contact information. - Stack
footer columns on small screens. - Ensure email and telephone links use
appropriate `mailto:` and `tel:` URLs when supported.

### Typography, colors, and visual tokens

The live text extraction does not reliably establish exact font
families, CSS color values, shadows, radii, or breakpoints. Confirm
these from the existing local styles and actual rendered website before
changing them.

Create or retain shared design tokens for: - Brand primary and secondary
colors. - Main text, muted text, page background, surface/card
background, border, success, warning, and error colors. - Heading/body
font families and sizes. - Spacing scale. - Border radius and shadows. -
Container widths and responsive breakpoints. - Focus-ring and
disabled-state styles.

Do not arbitrarily replace the existing brand palette. Check the current
logo, CSS variables, Tailwind configuration (if used), and live
rendering first.

## 3. Page specifications

### 3.1 Homepage --- `/`

Observed sections, in order:

1.  **Hero / promotional slider**
    -   Multiple promotional messages are present.
    -   "Built for Young Professionals" with supporting text about
        affordable services for students, entrepreneurs, and growing
        businesses; CTA: "Explore Services."
    -   "Your Link to Professional Services" with supporting text about
        browsing, ordering, and tracking services; CTA: "Order Now."
    -   "Shop Products & Digital Services" with supporting text about
        laptops, gadgets, airtime, and software; CTA: "Visit Shop."
    -   Slider controls and indicators are present.
2.  **Our Service Stores**
    -   Introductory text about five specialized stores.
    -   Five store cards/links:
        -   Engineering & Technical Services --- TRESCA ENGINEERING Ltd.
        -   Graphic Design & Media --- TRESCA GRAPHIC Ltd.
        -   Construction & Works --- TRESCA CONSTRUCTION Ltd.
        -   Online Shopping & Supplies --- TRESCA ONLINE Ltd.
        -   Refreshments & Event Support --- TRESCA REFRESHMENTS Ltd.
    -   Each store should expose the intended service-browsing action.
3.  **Popular Services**
    -   Section heading and short description.
    -   "View All" link.
    -   Service cards show title, "What's Included" list, starting price
        in RWF, and an order action.
    -   The live homepage examples include Plumbing Installation
        Package, Construction Supervision -- Monthly, Architectural
        Design -- Small House, and Land Survey -- Residential Plot.
4.  **Popular Products**
    -   Section heading and short description.
    -   "Visit Shop" link.
    -   Product cards show stock/type status, title, description, RWF
        price, delivery estimate, and order action.
    -   Live examples include Wireless Bluetooth Earbuds, Samsung Galaxy
        A15 Smartphone, Lenovo ThinkPad E14 (Business), and HP Laptop --
        15s (Core i5).
5.  **How It Works**
    -   Browse
    -   Add to Cart
    -   Checkout
    -   Track & Receive
    -   Keep these steps readable and ordered; ensure wording matches
        the actual supported workflow.
6.  **Audience / CTA cards**
    -   "For Youth" with a short description and "Learn More."
    -   "For Partners & Clients" with a short description and "Learn
        More."
    -   Verify these CTAs lead to their intended public destinations.
7.  **Testimonials**
    -   Existing client quotes and names/roles are displayed.
    -   "Share Your Experience" opens or displays a testimonial form.
    -   Form fields observed: Full Name, Position / Role, Email, Phone
        Number, Testimonial.
    -   The interface says testimonials are reviewed before publication.
    -   Verify open/close, validation, submission feedback, and error
        handling.
8.  **Our Partners**
    -   Display real business names and logos from vendors approved for
        public partner display.
    -   Show only active, published vendors whose public branding is
        approved; use a text fallback when a registered logo is missing.
    -   Use a static, responsive logo grid immediately above the footer.
9.  **Footer**
    -   Use the shared footer specification above.

Homepage acceptance checks: - Slider controls switch slides correctly
and do not cause layout shifts. - All store cards, service cards,
product cards, and CTAs open the correct pages/actions. - Product and
service prices/availability match the data returned by the
application. - Testimonial form can be opened, closed, validated, and
submitted without a page-breaking error. - Long product
titles/descriptions do not make card buttons misalign or overflow.

### 3.2 Services marketplace --- `/services`

Purpose: browse the five service stores and enter a specialist category.

Observed content: - Page heading: "Services Marketplace." - Introductory
copy explaining that service packages can be ordered like products. -
Five store cards with name, company label, description, service count,
and a browse action.

Store descriptions: - Engineering & Technical Services: engineering,
surveying, technical consulting. - Graphic Design & Media: design,
branding, media production. - Construction & Works: construction,
renovation, installation, finishing. - Online Shopping & Supplies:
electronics, gadgets, accessories, assisted purchasing. - Refreshments &
Event Support: beverages, catering support, event/meeting packages.

Acceptance checks: - Every store card navigates to its matching category
route. - Service counts reflect actual data. - Empty categories show a
clear empty state rather than broken markup. - Shared header/footer and
card styles match the rest of the site.

### 3.3 Service-store/category pages

Observed example route: `/services/engineering-technical-services`.

The page contains: - Store icon/identity. - Store title and
business/company name. - Store description. - Service package cards. -
Package title, summary, "What's Included" list, starting price, and
order action. - A subcategory/filter area; the inspected engineering
page displayed "No services found in this subcategory."

Other known store categories: - Graphic Design & Media. - Construction &
Works. - Online Shopping & Supplies. - Refreshments & Event Support.

Acceptance checks: - Store navigation and category filters work. - An
empty filter result is understandable and provides a way to clear/reset
the filter if supported. - Service data is not duplicated or assigned to
the wrong store. - "Order" opens the correct service detail/cart/order
flow. - Long inclusion lists wrap correctly on mobile.

### 3.4 Shop --- `/shop`

Purpose: browse physical products and digital services.

Observed elements: - Heading: "Shop & Order." - Introductory text
describing physical products, digital services, and tech accessories. -
Product-type filters: All Products, Digital Services, Physical. -
Product search input. - Product cards with product type/category, stock
status, name, description, price in RWF, delivery/activation estimate,
and order action. - "Can't Find What You Need?" assisted-purchase
section with "Request a Product" CTA. - Shared footer.

Observed examples: - Internet Data Bundle (Monthly). - Airtime Top-Up
(Any Network). - Wireless Bluetooth Earbuds. - Samsung Galaxy A15
Smartphone. - Lenovo ThinkPad E14 (Business). - HP Laptop -- 15s (Core
i5).

Acceptance checks: - Search filters products correctly and handles
no-match results. - Type filters update the visible products and counts
correctly. - "Clear Search" resets the search when visible. - Product
cards navigate to the matching detail page. - Physical/digital type,
stock status, price, and fulfilment estimate are accurate. - Request a
Product opens the correct request form. - Loading, empty, and error
states are visually distinct.

### 3.5 Product detail --- example `/shop/wireless-bluetooth-earbuds`

Observed structure: - Shared navigation. - Product gallery/main image
and gallery thumbnails. - Product type and stock status. -
Category/breadcrumb. - Product title and description. - Price in RWF. -
Delivery estimate. - "Order via WhatsApp" action. - Features / What's
Included list. - Specifications table. - "Ask Questions via WhatsApp"
action. - Related product cards.

Acceptance checks: - Gallery thumbnails update the main image and have
useful alternative text. - Missing product images use a graceful
placeholder rather than a broken image icon. - Specifications remain
readable on narrow screens. - WhatsApp links use the intended product
and a valid destination/encoded message. - Related products link to the
correct products. - Product details and cards use the same source of
truth for title, price, stock, and fulfilment data.

### 3.6 Request a Service --- `/request-service`

Observed form fields: - Full Name (required). - Phone (required). -
Email (optional in the observed form). - Service Category. - Describe
Your Requirements (required). - Budget Range. - Preferred Timeline. -
Optional file attachments. - Human verification math question. - Submit
Request button.

The page states that images, PDFs, and documents up to 5 MB each can be
attached.

Acceptance checks: - Required fields have clear labels and validation
messages. - Email/phone validation is appropriate. - File type and size
restrictions match backend validation. - Human-verification question
loads, refreshes, and validates correctly. - Submit shows a loading
state and clear success/error feedback. - Invalid submissions preserve
the user's entered values. - The form works on mobile and with keyboard
navigation.

### 3.7 For Partners & Clients --- `/for-partners`

Observed content: - Introductory section for corporate clients, NGOs,
and businesses. - Benefit blocks: - Dedicated Account Support. - Bulk &
Recurring Orders. - Transparent Tracking. - Partnership Opportunities. -
"Our Partners" section. - "Get in Touch" CTA. - Shared footer.

Acceptance checks: - CTA goes to its intended contact/request
destination. - Benefit blocks are consistently spaced and responsive. -
Repeated content is intentional; remove duplication only after checking
the actual local content and intended design.

### 3.8 Youth page

The homepage links to a youth-focused destination, but the destination
was not verified as a separate public page during this inspection. Find
its actual route in the local routes/navigation and document the current
page content before modifying it.

Requirements: - Preserve the intended youth opportunities, skills, or
services content currently implemented. - Verify every CTA and avoid
inventing sections not supported by the existing application.

### 3.9 Cart, checkout, and order tracking

The site describes a flow of browsing, adding to cart, checking out, and
tracking/receiving an order. These pages and interactions must be
located in the local route definitions and tested against the existing
implementation.

Expected UI/behavior to verify: - Cart item name, quantity, unit price,
subtotal, and total. - Quantity update and remove-item actions. -
Empty-cart state and link back to browsing. - Checkout customer/contact
details and any supported delivery/payment fields. - Validation,
submission state, confirmation, and error feedback. - Order tracking
lookup and clear status presentation. - No false success messages when a
request fails. - Cart contents should persist for the duration supported
by the current implementation.

Do not invent payment methods, tracking statuses, or checkout fields;
confirm them from the local code and actual working flow.

### 3.10 Login and account-related destinations

Some homepage CTA links appeared to redirect to `/login` during the
inspection. Confirm their intended destination in the local routes and
CTA configuration before changing them. Authentication and admin/vendor
screens are outside the initial public-frontend redesign scope unless a
public flow depends on them.

## 4. Reusable component requirements

Build on existing components where available. Avoid unnecessary
rewrites.

-   `PublicHeader` / navigation.
-   `PublicFooter`.
-   `HeroSlider`.
-   `SectionHeading`.
-   `StoreCard`.
-   `ServiceCard`.
-   `ProductCard`.
-   `PriceDisplay` (consistent RWF formatting).
-   `StockBadge` / product-type badge.
-   `SearchInput`.
-   `CategoryFilter`.
-   `EmptyState`.
-   `LoadingState` and `ErrorState`.
-   `FormField`, validation message, and submit button states.
-   `TestimonialForm`.
-   `ProductGallery`.
-   `SpecificationTable`.
-   `RelatedProducts`.
-   `Breadcrumbs` where used.

Component names are suggestions, not instructions to duplicate
components that already exist.

## 5. Responsive behavior

Test at minimum: - Small mobile: 320--375 px. - Larger mobile: 390--430
px. - Tablet: 768 px. - Laptop: 1024--1366 px. - Wide desktop: 1440 px
and above.

Requirements: - No horizontal page overflow. - Navigation remains usable
at all widths. - Cards use a sensible responsive column count and
consistent gaps. - Product detail layout stacks appropriately on
mobile. - Forms become single-column on narrow screens. - Buttons have
usable touch targets. - Images preserve aspect ratio and do not
stretch. - Long names, prices, labels, and validation messages wrap
without overlapping. - Modal/dialog content fits the viewport and can
scroll when needed.

Treat these as test widths, not claims about the existing CSS
breakpoints.

## 6. Accessibility and interaction states

-   Use semantic headings in a logical hierarchy.
-   Associate form labels with their inputs.
-   Provide meaningful image alternative text.
-   Ensure keyboard access and visible focus states.
-   Do not rely on color alone to communicate stock, errors, or status.
-   Use buttons for actions and links for navigation.
-   Provide disabled, loading, success, empty, and error states where
    relevant.
-   Keep dialogs dismissible and return focus appropriately.
-   Ensure contrast is sufficient for text and controls.
-   Respect reduced-motion preferences for animated UI where applicable.

## 7. Frontend debugging workflow

Follow this order and test existing behavior before introducing
improvements.

### Phase A --- Baseline and safety

1.  Confirm the correct local project and current Git branch.
2.  Run `git status` and commit or safely preserve existing work.
3.  Identify framework, entry points, public routes, shared layouts, CSS
    framework, and existing design tokens.
4.  Run the local application and capture current errors before editing.
5.  Compare the local rendering with the live reference page by page.

### Phase B --- Fix global layout first

1.  Header, navigation, container width, typography, spacing, and
    footer.
2.  Mobile navigation and responsive overflow.
3.  Shared cards, buttons, badges, form controls, and image behavior.
4.  Avoid replacing the entire stylesheet before finding the cause of a
    defect.

### Phase C --- Fix pages in this order

1.  Homepage.
2.  Services marketplace and each store/category page.
3.  Shop search and filters.
4.  Product details and WhatsApp order links.
5.  Request a Service form.
6.  Partners and youth-related routes.
7.  Cart, checkout, and order tracking.

### Phase D --- Verify interactions and backend integration

-   Check browser console and network errors.
-   Check every internal link and CTA.
-   Verify form validation and server responses.
-   Verify prices, inventory, service counts, and product information
    come from the existing backend.
-   Check image paths, failed requests, and broken asset URLs.
-   Check route behavior after refresh and direct URL entry.

### Phase E --- Regression checks

-   Test desktop and mobile widths.
-   Test empty, loading, validation-error, and success states.
-   Confirm fixes have not broken another page using the same component.
-   Run the project's existing tests, formatter, and build commands when
    available.
-   Review the Git diff before committing.

## 8. Bug log template

Record each discovered issue before fixing it.

  Field                Details
  -------------------- -----------------------------------------------------
  ID                   `FE-001`, `FE-002`, etc.
  Page/route           Exact URL or route name
  Component            Header, product card, form, etc.
  Expected behavior    What should happen
  Actual behavior      What currently happens
  Reproduction steps   Minimal steps to reproduce
  Evidence             Screenshot, console/network error, or relevant code
  Root cause           Fill in after investigation
  Fix                  Exact change made
  Verification         Browser/device and result
  Status               Open / Investigating / Fixed / Verified

## 9. Definition of done

A page is ready when: - Its layout matches the established site
structure and local brand styles. - All visible links and buttons work
as intended. - Data and prices match the backend. - Forms validate and
report results correctly. - Loading, empty, and error states are
handled. - No new console errors or failed asset requests are
introduced. - It works at the agreed mobile, tablet, and desktop test
widths. - The change has been reviewed and regression-tested.

## 10. Keep frontend work separate from admin-panel work

The current priority is the **public frontend**. Do not redesign the
admin panel while public-page defects remain.

After the frontend is stable: 1. Record the verified frontend routes and
shared components. 2. Commit the completed frontend fixes on the working
branch. 3. Begin a separate admin-panel inspection and debugging pass.
4. Preserve existing Laravel routes, permissions, workflows, and backend
behavior unless a verified defect requires a change.

## 11. Live reference routes inspected

-   Homepage: https://trescahub.rw/
-   Services marketplace: https://trescahub.rw/services
-   Engineering services example:
    https://trescahub.rw/services/engineering-technical-services
-   Graphic design services example:
    https://trescahub.rw/services/graphic-design-media
-   Shop: https://trescahub.rw/shop
-   Earbuds product detail example:
    https://trescahub.rw/shop/wireless-bluetooth-earbuds
-   Request a Service: https://trescahub.rw/request-service
-   For Partners & Clients: https://trescahub.rw/for-partners

Other page routes (including youth, cart, checkout, and tracking) must
be confirmed in the local route definitions and tested before changing
them.
