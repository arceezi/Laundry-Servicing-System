# (Codex-Ready) LE3 Laundry Servicing System — Build Spec + File Map (AI-First)

> **Source:** “Laboratory Exercise 3 — Laundry Servicing System Using Multidimensional Arrays in PHP” (uploaded PDF)  
> **Required main filename:** `LE3_ZabalaRalfCedricT.php`  
> **Submit:** `.php`, `.css`, `.html` (plus screenshots + explanation in a Word doc → PDF w/ watermark)

---

## 0) One-Screen Summary (What to implement)

Build a **single-page PHP web app** that:

1) Shows an HTML form where customer enters:
- Name
- Contact Number
- Weight in kg
- Service type: Regular Wash / Express Wash / Dry Cleaning

2) On submit (**Book Laundry**):
- Validate inputs
- Compute subtotal = weight * price_per_kg
- Apply discount:
  - weight > 20 ⇒ 15%
  - else if weight > 10 ⇒ 10%
  - else ⇒ 0%
- Store booking in a **multidimensional associative array** (session-based)
- Display **ALL bookings** in a dynamic HTML table
- Show a **New Entry Prompt** after successful booking

3) Must include a **Clear Details** button that resets form fields.

---

## 1) Business Rules (Do not deviate)

### 1.1 Price list (per kg)
- Regular Wash: **20.00**
- Express Wash: **30.50**
- Dry Cleaning: **50.00**

### 1.2 Discount thresholds (IMPORTANT edge cases)
- If **weight_kg > 20** → discount **15%**
- Else if **weight_kg > 10** → discount **10%**
- Else → discount **0%**

✅ That means:
- weight = 10.00 → 0%
- weight = 20.00 → 10% (since it is NOT above 20)
- weight = 20.01 → 15%

### 1.3 Output formatting rules
- Show money values with 2 decimals
- Show weight with up to 2 decimals (or 그대로 input)
- Show discount as `0%`, `10%`, `15%`

---

## 2) Architecture (Codex-friendly)

### 2.1 Runtime model (simple)
- Everything renders from `LE3_ZabalaRalfCedricT.php`
- Data persists via `$_SESSION`
- HTML templates exist as `.html` files to satisfy submission requirement
- CSS is in a separate `.css` file

### 2.2 Data flow
```
GET  /LE3_*.php
  -> render page (form + bookings table)

POST /LE3_*.php  action=book
  -> validate -> compute -> append to $_SESSION['bookings']
  -> render page + success + "New Entry?" prompt
```

---

## 3) Project File Tree (EXACT)

> Keep it small + easy to grade.

```
LE3_LaundrySystem/
├─ LE3_ZabalaRalfCedricT.php        # main controller + renderer
├─ assets/
│  └─ styles.css                          # required CSS
└─ templates/
   ├─ layout.html                         # required HTML file (page skeleton)
   ├─ form.html                           # form block
   ├─ table.html                          # table block + headers
   └─ prompt.html                         # new-entry prompt block
```

**Submission note:** If your instructor strictly wants an `.html` page at root, you can copy `templates/layout.html` to `index.html` too. But the structure above already includes `.html`.

---

## 4) UI Wireframes (Typewritten figures)

### 4.1 Single page layout
```
┌───────────────────────────────────────────────────────────┐
│ Laundry Servicing System (LE3)                             │
├───────────────────────────────────────────────────────────┤
│ [ALERTS AREA: errors / success]                            │
├───────────────────────────────────────────────────────────┤
│ FORM: Book a Laundry Service                               │
│ Name:            [______________]                          │
│ Contact Number:  [______________]                          │
│ Weight (kg):     [___.__]                                  │
│ Service Type:    [Regular ▼] (or radio buttons)            │
│                                                           │
│ [ Book Laundry ]   [ Clear Details ]                       │
├───────────────────────────────────────────────────────────┤
│ BOOKINGS TABLE (dynamic, shows all session bookings)       │
│ ┌───────────────────────────────────────────────────────┐ │
│ │ # | Name | Contact | kg | Service | Price/kg | Subtotal │ │
│ │   | Disc% | Disc Amt | Total | Timestamp               │ │
│ └───────────────────────────────────────────────────────┘ │
├───────────────────────────────────────────────────────────┤
│ New Entry Prompt (only after successful booking)           │
│ "Do you want to make a new entry?"                         │
│ [ Yes - New Entry ]  [ No ]                                │
└───────────────────────────────────────────────────────────┘
```

---

## 5) Data Structures (Multidimensional associative array)

### 5.1 Service catalog (associative array-of-arrays)
Use this exact shape:

```text
$SERVICES = [
  "regular" => ["label" => "Regular Wash", "price_per_kg" => 20.00],
  "express" => ["label" => "Express Wash", "price_per_kg" => 30.50],
  "dry"     => ["label" => "Dry Cleaning", "price_per_kg" => 50.00],
];
```

### 5.2 Bookings store (session-based multidimensional associative array)
```text
$_SESSION["bookings"] = [
  0 => [ "id"=>1, "name"=>"...", "contact"=>"...", ... ],
  1 => [ "id"=>2, "name"=>"...", "contact"=>"...", ... ],
  ...
];
$_SESSION["next_id"] = 3;
```

### 5.3 Booking record schema (EXACT keys recommended)
```text
[
  "id"            => int,
  "name"          => string,
  "contact"       => string,
  "weight_kg"     => float,
  "service_key"   => string,    // regular|express|dry
  "service_label" => string,    // Regular Wash|Express Wash|Dry Cleaning
  "price_per_kg"  => float,
  "subtotal"      => float,
  "discount_rate" => float,     // 0|0.10|0.15
  "discount_amt"  => float,
  "total"         => float,
  "created_at"    => string     // timestamp
]
```

---

## 6) Required Behaviors (Acceptance Criteria Checklist)

### 6.1 Form
- [ ] Has inputs: Name, Contact Number, Weight(kg), Service Type
- [ ] Book Laundry submits the form
- [ ] Clear Details resets form fields (must NOT delete stored bookings)

### 6.2 Discount logic
- [ ] weight > 10 => 10% discount
- [ ] weight > 20 => 15% discount
- [ ] weight = 10 => 0%
- [ ] weight = 20 => 10%

### 6.3 Table output
- [ ] Displays ALL bookings stored in session
- [ ] Shows computed totals and discount amounts
- [ ] Updates after each booking submit

### 6.4 New entry prompt
- [ ] Appears only after a successful booking
- [ ] “Yes - New Entry” clears form for the next booking (session table remains)
- [ ] “No” hides/dismisses prompt (table remains visible)

---

## 7) Implementation Plan (Step-by-step for Codex)

### Step 1 — Create files
Create these empty files first:
- `LE3_ZabalaRalfCedricT.php`
- `assets/styles.css`
- `templates/layout.html`
- `templates/form.html`
- `templates/table.html`
- `templates/prompt.html`

### Step 2 — Write HTML templates with placeholders (token-based)
Use placeholder tokens EXACTLY like this for easy `str_replace()`:

#### templates/layout.html
```html
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>{{PAGE_TITLE}}</title>
  <link rel="stylesheet" href="assets/styles.css" />
</head>
<body>
  <main class="container">
    <h1>{{PAGE_TITLE}}</h1>

    <section class="alerts">
      {{ALERTS_BLOCK}}
    </section>

    <section class="card">
      {{FORM_BLOCK}}
    </section>

    <section class="card">
      {{TABLE_BLOCK}}
    </section>

    <section class="card">
      {{PROMPT_BLOCK}}
    </section>
  </main>
</body>
</html>
```

#### templates/form.html
```html
<h2>Book a Laundry Service</h2>

<form method="post" action="">
  <input type="hidden" name="action" value="book" />

  <label>
    Name
    <input name="customer_name" value="{{VAL_NAME}}" required />
  </label>

  <label>
    Contact Number
    <input name="contact_number" value="{{VAL_CONTACT}}" required />
  </label>

  <label>
    Weight of Laundry (kg)
    <input name="weight_kg" value="{{VAL_WEIGHT}}" type="number" step="0.01" min="0.01" required />
  </label>

  <label>
    Service Type
    <select name="service_type" required>
      <option value="">-- Select --</option>
      <option value="regular" {{SEL_REGULAR}}>Regular Wash</option>
      <option value="express" {{SEL_EXPRESS}}>Express Wash</option>
      <option value="dry" {{SEL_DRY}}>Dry Cleaning</option>
    </select>
  </label>

  <div class="btn-row">
    <button type="submit">Book Laundry</button>
    <button type="reset">Clear Details</button>
  </div>
</form>
```

#### templates/table.html
```html
<h2>Booked Orders</h2>

<table>
  <thead>
    <tr>
      <th>#</th>
      <th>Name</th>
      <th>Contact</th>
      <th>kg</th>
      <th>Service</th>
      <th>Price/kg</th>
      <th>Subtotal</th>
      <th>Disc %</th>
      <th>Disc Amt</th>
      <th>Total</th>
      <th>Timestamp</th>
    </tr>
  </thead>
  <tbody>
    {{BOOKING_ROWS}}
  </tbody>
</table>
```

#### templates/prompt.html
```html
{{PROMPT_UI}}
```

---

## 8) PHP Controller Spec (Exact routing + session logic)

### 8.1 Must-have header comment block (per guidelines)
At the top of `LE3_ZabalaRalfCedricT.php`, include:

```text
Name: <Your Name>
Date Created: <YYYY-MM-DD>
Problem Description: Laundry Servicing System using multidimensional arrays in PHP to store bookings, compute total cost with discounts, and display results dynamically.
```

### 8.2 Session initialization
On every request:
- call `session_start()`
- if `$_SESSION['bookings']` not set → set to `[]`
- if `$_SESSION['next_id']` not set → set to `1`

### 8.3 Allowed actions
Use a single `action` field:
- `action=book` for booking submission

Use GET param for prompt actions:
- `?new=1` = dismiss prompt + clear form defaults
- `?new=0` = dismiss prompt only

### 8.4 Validation rules (minimum)
Reject booking if any:
- name empty
- contact empty
- weight not numeric or <= 0
- service not in `$SERVICES`

Return errors as an array of strings; render in `{{ALERTS_BLOCK}}`.

---

## 9) Computation Spec (Exact formulas)

Given:
- `$weightKg`
- `$pricePerKg`

Compute:
- `$subtotal = $weightKg * $pricePerKg`
- `$discountRate = (weightKg > 20) ? 0.15 : ((weightKg > 10) ? 0.10 : 0.00)`
- `$discountAmt = $subtotal * $discountRate`
- `$total = $subtotal - $discountAmt`

Formatting:
- `money(value) = number_format(value, 2)`
- `discountPercent = (int)round($discountRate * 100) . '%'`

---

## 10) Rendering Spec (No guessing — do exactly)

### 10.1 Rendering algorithm
1) Load templates:
- `layout.html`, `form.html`, `table.html`, `prompt.html`

2) Build blocks as strings:
- `$alertsBlock`
- `$formBlock` (inject values + selected option markers)
- `$bookingRows` (loop session bookings)
- `$tableBlock` (inject rows)
- `$promptBlock` (inject prompt UI or blank)

3) Inject blocks into layout with `str_replace()` map:
- `{{PAGE_TITLE}}` = "Laundry Servicing System (LE3)"
- `{{ALERTS_BLOCK}}`, `{{FORM_BLOCK}}`, `{{TABLE_BLOCK}}`, `{{PROMPT_BLOCK}}`

### 10.2 Booking rows builder (loop spec)
For each booking in `$_SESSION['bookings']`, output:
```html
<tr>
  <td>id</td>
  <td>name</td>
  <td>contact</td>
  <td>weight</td>
  <td>service_label</td>
  <td>price_per_kg formatted</td>
  <td>subtotal formatted</td>
  <td>disc %</td>
  <td>discount_amt formatted</td>
  <td>total formatted</td>
  <td>created_at</td>
</tr>
```

---

## 11) Prompt UI Spec (exact behaviors)

### 11.1 When to show it
Show prompt ONLY when:
- last POST action was a successful booking AND prompt not dismissed

### 11.2 Prompt HTML (recommended)
In `{{PROMPT_UI}}`, render:
```html
<div class="prompt">
  <p>Do you want to make a new entry?</p>
  <div class="btn-row">
    <a class="btn" href="?new=1">Yes - New Entry</a>
    <a class="btn" href="?new=0">No</a>
  </div>
</div>
```

### 11.3 What “Yes” does
- Dismiss prompt
- Clear values used to prefill form (set `VAL_*` tokens to empty)
- Keep session bookings intact

### 11.4 What “No” does
- Dismiss prompt only
- Keep table intact
- Form can remain blank (recommended) or retain last values (optional)

---

## 12) CSS Spec (minimum styling targets)

`assets/styles.css` must style:
- `.container` max-width + center
- `.card` padding + margin + border
- `label` display block + spacing
- `.btn-row` layout for buttons
- `table` full width + readable
- `.alerts .error` and `.alerts .success`
- `.prompt` highlight box

---

## 13) Rubric Mapping (Ensure points)

- **10 pts:** intelligent variable names + comments (PHP/HTML/CSS)
- **20 pts:** layout/design (clean UI)
- **20 pts:** correct computation + correct display
- **20 pts:** conditionals, loops, operators, arrays, functions
- **10 pts:** correct filename + followed instructions  
  ⚠️ Wrong filename penalty is huge — do not risk it.

---

## 14) Codex Build Order (Literal To-Do List)

1. Implement file loader helper: `loadTemplate($path)`
2. Implement `money($x)` + `discountRate($weightKg)` + `computeTotals(...)`
3. Implement `validate($post, $SERVICES)`
4. Implement session init
5. Implement POST booking handler:
   - sanitize + validate
   - compute
   - append booking
   - set `$_SESSION['show_prompt'] = true`
6. Implement GET prompt dismiss handler:
   - `new=1` → clear form defaults + hide prompt
   - `new=0` → hide prompt
7. Implement row renderer loop
8. Implement placeholder injection for:
   - `VAL_NAME`, `VAL_CONTACT`, `VAL_WEIGHT`
   - `SEL_*` markers
9. Render final HTML using `layout.html`

---

## 15) Quick Test Cases (Must pass)

- Regular, 5kg → subtotal 100.00, disc 0%, total 100.00
- Express, 11kg → subtotal 335.50, disc 10%, total 301.95
- Dry, 21kg → subtotal 1050.00, disc 15%, total 892.50
- Exactly 10kg → 0% discount
- Exactly 20kg → 10% discount

---

## 16) Notes for Submission Workflow (Non-code)
- Upload `.php`, `.css`, `.html` files
- Provide screenshots of code with explanation in Word
- Convert Word to PDF with watermark
- Avoid plagiarism penalties

---

## 17) Implementation Guardrails (for AI)
- Do not introduce a database
- Do not remove session usage
- Do not remove multidimensional associative array
- Do not change discount thresholds (“above” is strict `>`)
- Keep UI single page and table visible
