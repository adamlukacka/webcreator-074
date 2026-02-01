<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
              http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
{foreach from=$urls item=url}
<url>
  <loc>{$url.loc|escape:'html'}</loc>
  <lastmod>{$url.lastmod}</lastmod>
  <priority>{$url.priority}</priority>
</url>
{/foreach}
</urlset>
