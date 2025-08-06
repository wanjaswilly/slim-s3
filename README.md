# Slim Simple Site Skeleton

A lightweight, reusable Slim 4 + Twig boilerplate for static company or portfolio websites (4–7 pages). Ideal for fast deployment of simple informational pages like:

- Company Homepage
- About Us
- Mission / Vision
- Contact Page
- Team / Partners
- Impact / Services

---

## Features

-  Slim 4 routing
-  Twig templating
-  Tailwind support with vite
-  Clean layout with partials
-  Custom 404 & 500 error pages
-  No session/authentication overhead
-  Fast and easy to clone for new websites
-  A `slim` cli command for ease in creating or removing pages and serving the site

## Getting Started

### Prerequisites
- PHP 8.0+
- Composer
- Node.js 16+
- SQLite (for blog functionality)

### Installation

1. Clone the repository:
```bash
git clone https://github.com/wanjaswilly/slim-simple-site-skeleton.git ./company-site
cd company-site
```

2. Install dependencies:
```bash
composer install
npm install && npm run build
```

3. Configure environment:
```bash
cp .env.example .env
# Edit .env with your settings
```
4. Configure css & js:

Check the generated files in `public/build` and update `templates/layout.twig`

### Development
```bash
php slim serve
```
Visit `http://localhost:8000`

---

## Project Structure

```

company-site/
├── app/                  # Application core
│   ├── Controllers       # Request handlers
│   ├── Helpers           # Utilities (BlogGenerator, etc.)
│   └── Middlewares       # HTTP middleware
│   └── Models            # HTTP middleware
├── config/               # Configuration files
│   ├── app.php           # Main config
│   └── projects.php      # Projects data
├── database/             # Database migrations
├── public/               # Web root
│   ├── build/            # Compiled assets
│   └── images/           # Site images
├── resources/            # Frontend assets
│   ├── css/              # Custom styles
│   └── js/               # JavaScript
├── routes/               # Route definitions
│   └── web.php           # Main routes
├── templates/            # Twig templates
│   ├── layout.twig       # Base template
│   ├── partials/         # Reusable components
│   ├── pages/            # Page templates
│   └── errors/           # Error pages
├── .env.example          # Environment template
├── composer.json         # PHP dependencies
├── package.json          # JS dependencies
└── slim                  # Custom CLI tool

````

---



## 🛠 Slim CLI

Easily scaffold pages, partials, or run the local server using the built-in CLI tool.


### Using the CLI Tool

| Command | Description |
|---------|-------------|
| `php slim make:page about` | Create new page |
| `php slim remove:page about` | Remove page |
| `php slim make:partial footer` | Create new partial |
| `php slim make:controller controllerName` | Creates new controller with the name given |
| `php slim make:model modelName` | Creates new model with the name given |
| `php slim make:model modelName -m` | Creates new model with the name given and a migration for it |
| `php slim migrate` | runs all pending migrations |
| `php slim serve` | Start dev server |


---

## Error Pages

* `templates/errors/404.twig` handles Not Found errors
* `templates/errors/500.twig` handles general server errors

These are rendered automatically via middleware defined in `bootstrap.php`.

---

## 🔗 License

MIT License © 2025 Wilson Wanja



---
