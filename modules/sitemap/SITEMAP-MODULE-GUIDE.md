# WebCreator CMS - Sitemap Module

A dynamic XML sitemap generator module for WebCreator CMS (PHP 7.4 compatible).

## Features

- Generates XML sitemaps dynamically from database content
- Includes homepage, pages, articles (services), and news
- Configurable URL patterns and priorities
- Can be accessed directly or via sitemap.xml redirect

## Installation

### Step 1: Copy Module Files

Copy the sitemap module to your WebCreator installation:

```bash
# Copy the module class
cp sitemap.class.php /path/to/your/site/framework/modules/sitemap/sitemap.class.php

# Copy the template
cp sitemap.tpl.php /path/to/your/site/templates/smarty/sitemap.tpl.php
```

### Step 2: Configure .htaccess

Add this rewrite rule to your `.htaccess` file (after `RewriteEngine On`):

```apache
# Dynamic sitemap
RewriteRule ^sitemap.xml$ /sitemap/generate [L]
```

### Step 3: Customize for Your Site

Edit `sitemap.class.php` to match your database structure and URL patterns.

#### Database Tables

The module expects these tables (adjust column names as needed):

| Table | Required Columns | Description |
|-------|------------------|-------------|
| `page_content` | `seo_title`, `last_edited_date`, `is_active`, `is_default_page`, `language_id` | Static pages |
| `articles` | `article_seo_title`, `article_startDay`, `article_published`, `language_id` | Articles/Services |
| `news` | `seo_title`, `create_date`, `published`, `approved`, `language_id` | News items |

#### URL Patterns

Default URL patterns (customize in the class methods):

| Content Type | URL Pattern | Priority |
|--------------|-------------|----------|
| Homepage | `/` | 1.00 |
| Pages | `/{seo_title}.html` | 0.80 |
| Articles | `/sluzba/{seo_title}.html` | 0.70 |
| News | `/news/{seo_title}.html` | 0.60 |

## Configuration Examples

### Change Article URL Pattern

To change from `/sluzba/` to `/article/` or `/blog/`:

```php
// In getArticles() method, change:
'loc' => rtrim($this->baseUrl, '/') . '/sluzba/' . $article['article_seo_title'] . '.html',

// To:
'loc' => rtrim($this->baseUrl, '/') . '/article/' . $article['article_seo_title'] . '.html',
```

### Add Products to Sitemap

Add a new method for products:

```php
private function getProducts() {
    $urls = [];
    $lid = g('controller')->getLanguageId();

    $products = g('db')->getArr("
        SELECT
            seo_title,
            last_modified
        FROM products
        WHERE is_active = 1
            AND language_id = %n
            AND seo_title IS NOT NULL
            AND seo_title != ''
        ORDER BY last_modified DESC",
        $lid
    );

    if ($products) {
        foreach ($products as $product) {
            $urls[] = [
                'loc' => rtrim($this->baseUrl, '/') . '/product/' . $product['seo_title'] . '.html',
                'lastmod' => $product['last_modified']
                    ? date('c', strtotime($product['last_modified']))
                    : date('c'),
                'priority' => '0.60'
            ];
        }
    }

    return $urls;
}
```

Then add to `generate()` method:

```php
// Add products
$products = $this->getProducts();
$urls = array_merge($urls, $products);
```

### Disable News in Sitemap

Comment out or remove in `generate()` method:

```php
// Add news (disabled)
// $news = $this->getNews();
// $urls = array_merge($urls, $news);
```

## .htaccess Examples

### Basic Setup

```apache
RewriteEngine On

# Static robots.txt
RewriteRule ^robots.txt - [L]

# Dynamic sitemap
RewriteRule ^sitemap.xml$ /sitemap/generate [L]
```

### With URL Redirects (e.g., old URLs)

```apache
# Redirect old URLs to new pattern
RewriteRule ^projekt/([a-z0-9-_]+).html(.*)$ /sluzba/$1.html$2 [R=301,L]

# Service pages
RewriteRule ^sluzba/([a-z0-9-_]+).html$ /article/showArticle/$1 [L]
```

## Testing

### Direct Access

Visit your sitemap directly:

```
https://yoursite.com/sitemap/generate
```

### Via sitemap.xml

After .htaccess configuration:

```
https://yoursite.com/sitemap.xml
```

### Verify with curl

```bash
curl -s "https://yoursite.com/sitemap.xml" | head -20
```

## Troubleshooting

### Sitemap shows empty or only homepage

1. Check database table names match your installation
2. Verify column names (especially `article_published` vs `article_active`)
3. Check language_id matches (default is usually 1)
4. Enable debug: uncomment `g('db')->showDebug();` in the class

### 404 Error on sitemap.xml

1. Ensure .htaccess rewrite rule is in place
2. Check RewriteEngine is On
3. Verify mod_rewrite is enabled on server

### XML Parsing Errors

1. Check for PHP errors before XML output
2. Ensure no whitespace before `<?php` in sitemap.class.php
3. Verify template has no BOM or extra whitespace

## Files Structure

```
framework/modules/sitemap/
└── sitemap.class.php      # Main module class

templates/smarty/
└── sitemap.tpl.php        # XML output template
```

## Compatibility

- PHP 7.4+
- WebCreator CMS
- Smarty Template Engine

## License

Part of WebCreator CMS. Use freely for your projects.
