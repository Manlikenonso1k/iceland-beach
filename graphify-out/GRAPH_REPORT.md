# Graph Report - gstage  (2026-09-02)

## Corpus Check
- 246 files · ~57,306 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1220 nodes · 2376 edges · 138 communities (47 shown, 30 thin omitted)
- Extraction: 97% EXTRACTED · 3% INFERRED · 0% AMBIGUOUS · INFERRED: 70 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- Ticket
- Booking
- composer.json
- dependencies
- Illuminate\Database\Seeder
- BookingResource.php
- BookingResource
- Filament\Resources\Pages\EditRecord
- Illuminate\Bus\Queueable
- scripts
- AdminPanelProvider.php
- Filament\Resources\Pages\ListRecords
- Filament\Resources\Resource
- Invoice
- TestCase
- User
- app.jsx
- GalleryPage.jsx
- InvoiceResource
- Illuminate\Support\Facades\DB
- Sale
- RolePolicy
- Filament\Resources\Pages\CreateRecord
- Illuminate\Database\Eloquent\Relations\BelongsTo
- BookingWorkflowService
- Filament\Forms\Form
- Illuminate\Database\Eloquent\Model
- NairaAmountFormatter
- Illuminate\Database\Eloquent\SoftDeletes
- AboutPage.jsx
- Setting
- SaleResource.php
- Filament\Actions
- Room
- Referral
- static
- Illuminate\Database\Eloquent\Relations\HasMany
- Filament\Tables\Table
- TicketResource
- Product
- Illuminate\Support\Facades\Schema
- Illuminate\Database\Schema\Blueprint
- Illuminate\Database\Migrations\Migration
- HomePage.jsx
- BookingConfirmedMail
- ActivityLogResource
- ReferralResource
- RoomResource
- AppServiceProvider.php
- SaleResource
- ServiceBooking
- User.php
- BookingWorkflowServiceTest
- Illuminate\Support\Str
- UserFactory.php
- RoomsPage.jsx
- BookingReceiptTest
- TicketScanLogResource
- SaleItem.php
- logging.php
- ServicesPage.jsx
- Login
- ListReports.php
- ActivityLogPolicy.php
- bootstrap/app.php
- vendor-spot-map.blade.php
- 2026_08_29_160337_add_room_id_and_check_out_to_bookings_table.php
- Footer.jsx
- ServiceBookingPage.jsx
- Dashboard
- ListReferrals.php
- console.php
- .content
- .envelope
- .referral
- deploy.sh
- manage-settings.blade.php

## God Nodes (most connected - your core abstractions)
1. `User` - 76 edges
2. `Booking` - 59 edges
3. `Room` - 55 edges
4. `Ticket` - 33 edges
5. `Invoice` - 21 edges
6. `Sale` - 20 edges
7. `BookingConfirmedMail` - 16 edges
8. `Referral` - 16 edges
9. `InvoicePolicy` - 16 edges
10. `BookingWorkflowService` - 16 edges

## Surprising Connections (you probably didn't know these)
- `BookingController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/Api/BookingController.php → app/Http/Controllers/Controller.php
- `BookingPaymentController` --references--> `BookingWorkflowService`  [EXTRACTED]
  app/Http/Controllers/BookingPaymentController.php → app/Services/BookingWorkflowService.php
- `BookingReceiptController` --inherits--> `Controller`  [EXTRACTED]
  app/Http/Controllers/BookingReceiptController.php → app/Http/Controllers/Controller.php
- `PaystackWebhookController` --references--> `BookingWorkflowService`  [EXTRACTED]
  app/Http/Controllers/PaystackWebhookController.php → app/Services/BookingWorkflowService.php
- `BookingConfirmedMail` --references--> `Booking`  [EXTRACTED]
  app/Mail/BookingConfirmedMail.php → app/Models/Booking.php

## Import Cycles
- None detected.

## Communities (138 total, 30 thin omitted)

### Community 0 - "Ticket"
Cohesion: 0.05
Nodes (25): CatalogController, ContactController, ServiceBookingController, BookingPaymentController, Controller, PaymentController, PaystackWebhookController, VendorPaymentController (+17 more)

### Community 1 - "Booking"
Cohesion: 0.05
Nodes (20): CreateBooking, EditBooking, ConfirmedBookingsWidget, PaymentMethodBreakdown, PendingBookingsWidget, Carbon, RevenueStatsWidget, RoomBreakdownPieChart (+12 more)

### Community 2 - "composer.json"
Cohesion: 0.04
Nodes (48): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+40 more)

### Community 3 - "dependencies"
Cohesion: 0.05
Nodes (42): axios, concurrently, framer-motion, gsap, laravel-vite-plugin, lucide-react, dependencies, framer-motion (+34 more)

### Community 4 - "Illuminate\Database\Seeder"
Cohesion: 0.08
Nodes (13): VendorSpotMap, VendorSpot, AdminUserSeeder, DatabaseSeeder, LegacyRoomsSeeder, ProductionSqlCatalogSeeder, RoleSeeder, VendorSpotSeeder (+5 more)

### Community 5 - "BookingResource.php"
Cohesion: 0.14
Nodes (24): App\Filament\Resources\BookingResource\Pages, App\Filament\Resources\ReferralResource\Pages, App\Filament\Resources\RoomResource\Pages, App\Filament\Resources\SpaceResource\Pages, App\Filament\Resources\TicketResource\Pages, App\Filament\Resources\VendorRequestResource\Pages, Filament\Facades\Filament, Filament\Forms\Components\DateTimePicker (+16 more)

### Community 6 - "BookingResource"
Cohesion: 0.11
Nodes (10): BookingResource, BookingCalendar, ViewTicket, ViewVendorRequest, BookingCalendarWidget, Filament\Actions\Action, Filament\Resources\Pages\Page, Filament\Resources\Pages\ViewRecord (+2 more)

### Community 7 - "Filament\Resources\Pages\EditRecord"
Cohesion: 0.10
Nodes (10): EditInventoryItem, EditReferral, EditReport, EditRole, EditRoom, EditSale, EditSpace, EditTicketScanLog (+2 more)

### Community 8 - "Illuminate\Bus\Queueable"
Cohesion: 0.19
Nodes (14): BookingCustomerConfirmationMail, Content, Envelope, Content, Envelope, SaleReceiptMail, Content, Envelope (+6 more)

### Community 9 - "scripts"
Cohesion: 0.08
Nodes (27): scripts, dev, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, pre-package-uninstall, setup (+19 more)

### Community 10 - "AdminPanelProvider.php"
Cohesion: 0.08
Nodes (21): AdminPanelProvider, Filament\Http\Middleware\Authenticate, Filament\Http\Middleware\AuthenticateSession, Filament\Http\Middleware\DisableBladeIconComponents, Filament\Http\Middleware\DispatchServingFilamentEvent, Filament\Pages, Filament\Panel, Filament\PanelProvider (+13 more)

### Community 11 - "Filament\Resources\Pages\ListRecords"
Cohesion: 0.11
Nodes (9): ListActivityLogs, ListBookings, ListInventoryItems, ListRoles, ListRooms, ListSales, ListTickets, ListVendorRequests (+1 more)

### Community 12 - "Filament\Resources\Resource"
Cohesion: 0.11
Nodes (11): App\Filament\Resources\ActivityLogResource\Pages, App\Filament\Resources\RoleResource\Pages, RoleResource, App\Filament\Resources\TicketScanLogResource\Pages, App\Filament\Resources\TicketScanLogResource\RelationManagers, App\Filament\Resources\UserResource\Pages, UserResource, Filament\Forms (+3 more)

### Community 14 - "TestCase"
Cohesion: 0.15
Nodes (8): Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, Illuminate\Support\Facades\URL, BookingCalendarWidgetTest, ExampleTest, InvoiceModelTest, TicketPaymentRefreshServiceTest, TestCase

### Community 15 - "User"
Cohesion: 0.18
Nodes (3): User, UserPolicy, Illuminate\Foundation\Auth\User

### Community 16 - "app.jsx"
Cohesion: 0.12
Nodes (7): api, money, Header(), navLinks, events, EventsPage(), GalleryPage()

### Community 17 - "GalleryPage.jsx"
Cohesion: 0.20
Nodes (16): blurVariants, ContainerAnimated, ContainerScroll(), ContainerScrollContext, ContainerStagger, ContainerSticky(), GalleryCol(), GalleryContainer() (+8 more)

### Community 18 - "InvoiceResource"
Cohesion: 0.12
Nodes (6): InvoiceResource, EditInvoice, ListInvoices, Filament\Forms\Get, Filament\Forms\Set, Symfony\Component\HttpFoundation\StreamedResponse

### Community 19 - "Illuminate\Support\Facades\DB"
Cohesion: 0.12
Nodes (3): MostBookedRoomsWidget, Filament\Widgets\Widget, Illuminate\Support\Facades\DB

### Community 20 - "Sale"
Cohesion: 0.16
Nodes (4): Sale, SaleItem, PosService, Illuminate\Validation\ValidationException

### Community 22 - "Filament\Resources\Pages\CreateRecord"
Cohesion: 0.18
Nodes (8): CreateInvoice, CreateReport, CreateRole, CreateRoom, CreateSpace, CreateTicketScanLog, CreateUser, Filament\Resources\Pages\CreateRecord

### Community 23 - "Illuminate\Database\Eloquent\Relations\BelongsTo"
Cohesion: 0.17
Nodes (4): ActivityLog, ReferralTicketSale, VoidLog, Illuminate\Database\Eloquent\Relations\BelongsTo

### Community 24 - "BookingWorkflowService"
Cohesion: 0.18
Nodes (4): BookingDeclinedMail, Content, Envelope, BookingWorkflowService

### Community 25 - "Filament\Forms\Form"
Cohesion: 0.15
Nodes (4): App\Filament\Resources\ReportResource\Pages, ReportResource, VendorRequestResource, Filament\Forms\Form

### Community 26 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.31
Nodes (5): CreateSale, AuditObserver, ActivityLogService, Illuminate\Database\Eloquent\Model, Illuminate\Support\Facades\Auth

### Community 27 - "NairaAmountFormatter"
Cohesion: 0.20
Nodes (5): NairaAmountFormatter, PHPUnit\Framework\TestCase, self, ExampleTest, NairaAmountFormatterTest

### Community 28 - "Illuminate\Database\Eloquent\SoftDeletes"
Cohesion: 0.19
Nodes (5): InventoryItem, InvoiceItem, Membership, Illuminate\Database\Eloquent\SoftDeletes, Illuminate\Support\Facades\Storage

### Community 29 - "AboutPage.jsx"
Cohesion: 0.19
Nodes (8): AuroraCanvas, AuroraLayer(), AboutPage(), features, revealUp, staggerGrid, ContactPage(), revealUp

### Community 30 - "Setting"
Cohesion: 0.20
Nodes (4): ManageSettings, ScanTicket, Setting, Filament\Pages\Page

### Community 31 - "SaleResource.php"
Cohesion: 0.23
Nodes (9): App\Filament\Resources\InventoryItemResource\Pages, App\Filament\Resources\InvoiceResource\Pages, App\Filament\Resources\SaleResource\Pages, Filament\Forms\Components\DatePicker, Filament\Forms\Components\Repeater, Filament\Infolists\Components\TextEntry, Filament\Tables\Actions\DeleteAction, Filament\Tables\Actions\EditAction (+1 more)

### Community 32 - "Filament\Actions"
Cohesion: 0.17
Nodes (5): CreateInventoryItem, ListSpaces, ListTicketScanLogs, ListUsers, Filament\Actions

### Community 33 - "Room"
Cohesion: 0.21
Nodes (4): BookingNotificationMail, Content, Envelope, Room

### Community 36 - "Illuminate\Database\Eloquent\Relations\HasMany"
Cohesion: 0.24
Nodes (3): DiningTable, Waiter, Illuminate\Database\Eloquent\Relations\HasMany

### Community 37 - "Filament\Tables\Table"
Cohesion: 0.24
Nodes (4): InventoryItemResource, SalesRelationManager, Filament\Resources\RelationManagers\RelationManager, Filament\Tables\Table

### Community 39 - "Product"
Cohesion: 0.27
Nodes (4): SalesOverview, Product, Filament\Widgets\StatsOverviewWidget, Filament\Widgets\StatsOverviewWidget\Stat

### Community 43 - "HomePage.jsx"
Cohesion: 0.24
Nodes (8): ScrollExpandMedia(), confidenceCards, HomePage(), photoStripCards, revealUp, staggerGrid, stayCards, useCarousel()

### Community 44 - "BookingConfirmedMail"
Cohesion: 0.31
Nodes (3): BookingConfirmedMail, Content, Envelope

### Community 48 - "AppServiceProvider.php"
Cohesion: 0.29
Nodes (5): AppServiceProvider, Illuminate\Auth\Events\Login, Illuminate\Auth\Events\Logout, Illuminate\Support\Facades\Event, Illuminate\Support\ServiceProvider

### Community 51 - "User.php"
Cohesion: 0.29
Nodes (5): Filament\Models\Contracts\FilamentUser, Illuminate\Database\Eloquent\Factories\HasFactory, Illuminate\Notifications\Notifiable, Laravel\Sanctum\HasApiTokens, Spatie\Permission\Traits\HasRoles

### Community 54 - "UserFactory.php"
Cohesion: 0.40
Nodes (3): UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Facades\Hash

### Community 55 - "RoomsPage.jsx"
Cohesion: 0.47
Nodes (5): amenities, api, categoryFor(), formatMoney(), RoomsPage()

### Community 59 - "logging.php"
Cohesion: 0.40
Nodes (4): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler, Monolog\Processor\PsrLogMessageProcessor

### Community 60 - "ServicesPage.jsx"
Cohesion: 0.60
Nodes (4): api, imagePath(), money(), ServicesPage()

### Community 64 - "bootstrap/app.php"
Cohesion: 0.50
Nodes (3): Illuminate\Foundation\Application, Illuminate\Foundation\Configuration\Exceptions, Illuminate\Foundation\Configuration\Middleware

### Community 65 - "vendor-spot-map.blade.php"
Cohesion: 0.50
Nodes (3): closeModal, closeSuccess, selectSpot({{ $spot->id }})

### Community 67 - "Footer.jsx"
Cohesion: 0.50
Nodes (3): Footer(), schema, socialLinks

### Community 68 - "ServiceBookingPage.jsx"
Cohesion: 0.50
Nodes (3): api, money, ServiceBookingPage()

## Knowledge Gaps
- **106 isolated node(s):** `$schema`, `name`, `type`, `description`, `laravel` (+101 more)
  These have ≤1 connection - possible missing edges or undocumented components. (Counts symbols only; 421 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **30 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User` to `Ticket`, `Booking`, `.referral`, `Illuminate\Database\Seeder`, `BookingResource.php`, `AdminPanelProvider.php`, `Filament\Resources\Resource`, `Invoice`, `ReferralResource`, `AppServiceProvider.php`, `User.php`, `RolePolicy`, `UserFactory.php`, `Illuminate\Database\Eloquent\Model`, `ActivityLogPolicy.php`?**
  _High betweenness centrality (0.175) - this node is a cross-community bridge._
- **Why does `Booking` connect `Booking` to `Ticket`, `BookingReceiptTest`, `BookingResource.php`, `BookingResource`, `Illuminate\Bus\Queueable`, `BookingConfirmedMail`, `TestCase`, `BookingWorkflowServiceTest`, `Illuminate\Database\Eloquent\Relations\BelongsTo`, `BookingWorkflowService`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.097) - this node is a cross-community bridge._
- **Why does `Ticket` connect `Ticket` to `Booking`, `static`, `BookingResource.php`, `BookingResource`, `Illuminate\Bus\Queueable`, `TestCase`, `User.php`, `Illuminate\Database\Eloquent\Model`?**
  _High betweenness centrality (0.058) - this node is a cross-community bridge._
- **What connects `$schema`, `name`, `type` to the rest of the system?**
  _106 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `Ticket` be split into smaller, more focused modules?**
  _Cohesion score 0.05303030303030303 - nodes in this community are weakly interconnected._
- **Should `Booking` be split into smaller, more focused modules?**
  _Cohesion score 0.05031645569620253 - nodes in this community are weakly interconnected._
- **Should `composer.json` be split into smaller, more focused modules?**
  _Cohesion score 0.04081632653061224 - nodes in this community are weakly interconnected._