## About the Project


Within this Laravel & Vue JS bulk ticket purchasing system:
TicketController.php handles HTTP requests for purchasing tickets and checking the status of currently purchased tickets,
ProcessTicketPurchase.php job processes purchases by assigning random available tickets to users, 
ReseedTickets.php job automatically generates new tickets when available tickets are low, 
and DeleteSoldTickets.php runs daily via Laravel's scheduler to delete sold tickets. 
The Ticket.php, Purchase.php, and TicketResult.php models manage the database relationships, while TicketFactory.php generates initial tickets with realistic win probabilities. 
TicketSeeder.php and UserSeeder.php populate the database with initial tickets and a test user,
Index.vue provides real-time progress tracking and results display, 
and TicketPurchaseTest.php and TicketModelTest.php ensure the purchasing and win distributions work correctly through automated testing.


## Project Structure

```
ticket-system/
├── app/
│   ├── Http/
│   │   └── Controllers/
│   │       └── TicketController.php
│   ├── Jobs/
│   │   └── ProcessTicketPurchase.php
│   │   └── ReseedTickets.php
│   │   └── DeleteSoldTickets.php
│   └── Models/
│       ├── Purchase.php
│       ├── Ticket.php
│       ├── TicketResult.php
│       └── User.php
├── database/
│   ├── factories/
│   │   ├── TicketFactory.php
│   │   └── UserFactory.php
│   ├── migrations/
│   │   ├── create_tickets_table.php
│   │   ├── create_purchases_table.php
│   │   └── create_ticket_results_table.php
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── TicketSeeder.php
│       └── UserSeeder.php
├── resources/
│   └── js/
│       └── Pages/
│           └── Tickets/
│               └── Index.vue
├── routes/
│   └── web.php
├── tests/
│   ├── Feature/
│   │   └── TicketPurchaseTest.php
│   └── Unit/
│       └── TicketModelTest.php
├── .env
```


## Pitch for Expansion Using Laravel

Although this project is already built in Laravel, 
I have some expansion ideas that could transform this ticket purchasing system into a more user interactive lottery platform:

Real-time features could be implemented using Laravel WebSockets or Pusher to show live ticket processing, winner announcements, 
and purchase statistics across all users. 

Payment gateway integration with Stripe or PayPal would enable real money transactions, 
so we could then limit user purchasing based on their current budget.

Create a ticket type model with different types for different tickets; 
for example having certain winning tickets be of a certain ticket type that gives them access to interact with a new feature of the site.



## Local Installation

**Install required packages**
```
composer require laravel/breeze
npm install
npm install vue@next @vitejs/plugin-vue
npm install @headlessui/vue @heroicons/vue
```

**Setup Breeze with Vue**
```
php artisan breeze:install vue
npm run build
```

**Database setup**
```
php artisan migrate
```

**Update .env**
```
envDB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ticket_system
DB_USERNAME=your_username
DB_PASSWORD=your_password

QUEUE_CONNECTION=database
```

**Setup localhost**

```
php artisan serve

npm run dev
```

**Run seeders, phpunit tets, queued & scheduled jobs**
```
php artisan db:seed

php artisan test

php artisan queue:work
```
