=== PROMPT FOR AGENT 3 (Google Stitch) ===

Design the mobile UI for "Hospi Sales", a white-label hospitality operations app
for beach resorts and similar venues. Android and iOS, mobile portrait, light
mode only.

--------------------------------------------------------------------
PRIORITY 1 — WHAT THIS APP IS FOR. DESIGN THIS FIRST.
--------------------------------------------------------------------
This app exists to kill meal-order delays and make them accountable. The real
problem it solves: a guest orders food, it arrives late, and the manager says
"I wasn't aware there was a delay."

So the centrepiece of the whole design is a live, unmissable ORDER BOARD. Every
order carries a countdown against a per-meal target time. The manager sees red
the instant an order goes overdue. Design that screen first and design it best;
every other screen is secondary to it.

--------------------------------------------------------------------
CRITICAL: THIS IS WHITE-LABEL
--------------------------------------------------------------------
Do NOT put "Iceland Beach" anywhere in the designs. The business logo, business
name and brand colour are supplied per customer at runtime. Show them as
placeholders labelled "[TENANT LOGO]" and "[BUSINESS NAME]". Each business also
enables only some departments, so the home screen and bottom navigation must
look correct with as few as two departments and as many as six.

--------------------------------------------------------------------
DESIGN SYSTEM
--------------------------------------------------------------------
- Primary: deep navy #002F61. Secondary: white.
- Status colours, used consistently and only for status:
    GREEN  #16A34A  completed on time / paid
    AMBER  #F59E0B  pending, still within target time
    RED    #DC2626  OVERDUE or declined
    GREY   #64748B  completed late / inactive
- Rounded cards, 12-16px radius, soft shadow, generous white space.
- LARGE tap targets, minimum 48x48pt. Staff use this fast, one-handed, standing
  up, on a beach, sometimes with wet hands. Nothing small or fiddly.
- Bottom-tab navigation. Tabs vary by role and by enabled departments.
- Typography: one sans-serif family, clear hierarchy, numbers and timers large
  enough to read at arm's length.
- Every component must map cleanly onto a React Native primitive: View, Text,
  FlatList, Pressable, Modal, TextInput. No CSS grid, no hover states, no
  desktop-only affordances.

--------------------------------------------------------------------
SCREENS TO DESIGN
--------------------------------------------------------------------

1. WELCOME / FIRST-RUN SETUP
   Large [TENANT LOGO], [BUSINESS NAME] as the title, a short tagline, and a
   single primary "Get started" button. This is the first thing a new business
   sees, so it should feel like their app, not ours.

2. LOGIN
   Email OR phone number in one field (the app accepts either), password,
   "Sign in". Inline error state. Small [TENANT LOGO] in the header.

3. ROLE-BASED HOME — design FIVE variants, they are genuinely different:
   - CEO: revenue across all departments, period switcher (day/week/month),
     a compact count of overdue orders as a red alert strip.
   - MANAGER: the Order Board is the home screen. Do not bury it behind a tab.
   - WAITRESS: one huge "Take an order" button plus her own recent orders with
     their live timers. Nothing else.
   - CHEF: straight into the kitchen queue for that chef's department only.
   - HOST: tables, beds and cabins availability at a glance.

4. *** CENTREPIECE: MANAGER ORDER BOARD ***
   A live list of every order, sorted RED first, then AMBER, then GREEN.
   - RED / overdue must be unmissable: red left border, red count badge in the
     header, and a sticky "3 ORDERS OVERDUE" banner pinned to the top of the
     list. Design it so a manager glancing at the phone from two metres away
     cannot miss it.
   - Each order card shows: order number, the meal name(s) and quantities,
     the department badge (Main Kitchen / Barbecue / Ice Cream), the table or
     room reference, who took the order, a large countdown ("4:32 left" in
     amber, or "9:14 OVERDUE" in red), and elapsed-vs-target as a thin progress
     bar that fills and turns red past 100%.
   - Filter chips across the top: All / Overdue / In progress / Completed, and
     a department filter.
   - Tapping a card opens an order detail sheet with the full item list, the
     timeline (placed, accepted, ready, completed) and a decline reason when
     there is one.
   Design the empty state ("No active orders") and the all-clear state too.

5. WAITRESS — TAKE AN ORDER
   Dead simple, three steps, as few taps as possible:
   Step 1: pick a table or type a room/guest reference.
   Step 2: pick meals from a searchable grid, tap to add, quantity stepper on
           each selected row, optional note field ("no pepper").
   Step 3: review and submit.
   On submit, show a confirmation that VISIBLY starts the timer — a countdown
   appearing with the target time, so she can see the clock is now running.
   Show each meal's target prep time on its card in the grid.

6. CHEF — KITCHEN QUEUE
   Only this chef's department. Orders oldest-first with the most urgent at top.
   Each card: items and quantities, table reference, live countdown, and three
   large actions: ACCEPT, DECLINE, MARK READY.
   DECLINE must open a modal that REQUIRES a typed reason before the confirm
   button enables — design the disabled and enabled states of that button.
   Also design an e-commerce style meal grid for the department's catalogue,
   with each meal's price and prep time.

7. MANAGER SETTINGS — PRICES AND PREP TIMES
   Two sections. Prices: gate ticket, per-head charge, swimming per-head, each
   an editable currency field in naira. Prep times: a searchable list of every
   meal with an editable "target minutes" stepper per meal. Show a saved /
   unsaved indicator. Make clear that changing a prep time affects future
   orders only.

8. GATE SALES
   Today's ticket count and revenue, a "record sale" flow (ticket quantity plus
   per-head count, with the total computed and shown before confirming), and a
   receipts list filterable BY DAY and BY SHIFT with a download action on each.

9. HOSTS — TABLES / BEDS / CABINS
   Category tabs, availability grid with clear free/occupied states, and a
   booking flow ending in an invoice.

10. FRONT DESK — ROOMS + SWIMMING
    Room list with per-date availability, a date-range picker, a swimming entry
    flow charged per head, and a booking summary.

11. ICE CREAM COUNTER
    Fast product grid, running order total, confirm sale. Same countdown
    treatment as the kitchens, because these items also carry a prep target and
    appear on the same Order Board.

12. RECEIPT / INVOICE
    Clean printable-looking layout: [TENANT LOGO], business name, invoice
    number, date, itemised lines with quantity and unit price, total, amount in
    words, bank details block. A download/share action. Amounts in naira (₦).

13. NOTIFICATIONS
    An in-app list with unread state, grouped by day. Distinguish clearly
    between "new order" and "ORDER OVERDUE" — the overdue entries should read as
    alarming. Also design the push notification copy for both.

--------------------------------------------------------------------
DELIVERABLES
--------------------------------------------------------------------
Every screen in mobile portrait, light mode. For the Order Board also deliver
the three states side by side: all green, mixed, and several overdue. Include
loading, empty and error states for any screen that loads a list. Note the
component name and the status colour token used on each element, so a React
Native developer can implement it without guessing.
