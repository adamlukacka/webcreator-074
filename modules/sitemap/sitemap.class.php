<?php
/**
 * Sitemap Generator Module
 * Generates XML sitemaps dynamically from database content
 *
 * @package WebCreator CMS
 * @version 1.0
 */

class sitemap extends controller {
    public $default_action = 'generate';

    /**
     * Base URL for sitemap entries
     * @var string
     */
    private $baseUrl;

    /**
     * Constructor
     */
    function __construct() {
        $this->baseUrl = URL_ROOT;
    }

    /**
     * Generate and output the sitemap XML
     * Combines all content types into a single sitemap
     */
    public function generate() {
        // Set XML content type header
        header('Content-Type: application/xml; charset=utf-8');

        // Collect all URLs
        $urls = [];

        // Add homepage
        $urls[] = [
            'loc' => rtrim($this->baseUrl, '/') . '/',
            'lastmod' => date('c'),
            'priority' => '1.00'
        ];

        // Add pages
        $pages = $this->getPages();
        $urls = array_merge($urls, $pages);

        // Add articles (services)
        $articles = $this->getArticles();
        $urls = array_merge($urls, $articles);

        // Add news
        $news = $this->getNews();
        $urls = array_merge($urls, $news);

        // Assign to view and render
        g('view')->assign('urls', $urls);
        g('view')->setTemplate('sitemap');
    }

    /**
     * Get all active pages for sitemap
     *
     * @return array Array of URL entries
     */
    private function getPages() {
        $urls = [];
        $lid = g('controller')->getLanguageId();

        $pages = g('db')->getArr("
            SELECT
                seo_title,
                last_edited_date,
                is_default_page
            FROM page_content
            WHERE is_active = 1
                AND language_id = %n
                AND seo_title IS NOT NULL
                AND seo_title != ''
            ORDER BY last_edited_date DESC",
            $lid
        ); // g('db')->showDebug();

        if ($pages) {
            foreach ($pages as $page) {
                // Skip if this is the default/homepage (already added)
                if (isset($page['is_default_page']) && $page['is_default_page'] == 1) {
                    continue;
                }

                $urls[] = [
                    'loc' => rtrim($this->baseUrl, '/') . '/' . $page['seo_title'] . '.html',
                    'lastmod' => $page['last_edited_date']
                        ? date('c', strtotime($page['last_edited_date']))
                        : date('c'),
                    'priority' => '0.80'
                ];
            }
        }

        return $urls;
    }

    /**
     * Get all active articles (services) for sitemap
     *
     * @return array Array of URL entries
     */
    private function getArticles() {
        $urls = [];
        $lid = g('controller')->getLanguageId();

        $articles = g('db')->getArr("
            SELECT
                article_seo_title,
                article_startDay
            FROM articles
            WHERE article_published = 1
                AND language_id = %n
                AND article_seo_title IS NOT NULL
                AND article_seo_title != ''
            ORDER BY article_startDay DESC",
            $lid
        );

        if ($articles) {
            foreach ($articles as $article) {
                $urls[] = [
                    'loc' => rtrim($this->baseUrl, '/') . '/sluzba/' . $article['article_seo_title'] . '.html',
                    'lastmod' => $article['article_startDay']
                        ? date('c', strtotime($article['article_startDay']))
                        : date('c'),
                    'priority' => '0.70'
                ];
            }
        }

        return $urls;
    }

    /**
     * Get all published news for sitemap
     *
     * @return array Array of URL entries
     */
    private function getNews() {
        $urls = [];
        $lid = g('controller')->getLanguageId();

        $news = g('db')->getArr("
            SELECT
                seo_title,
                create_date
            FROM news
            WHERE published = 1
                AND approved = 1
                AND language_id = %n
                AND seo_title IS NOT NULL
                AND seo_title != ''
            ORDER BY create_date DESC",
            $lid
        );

        if ($news) {
            foreach ($news as $item) {
                $urls[] = [
                    'loc' => rtrim($this->baseUrl, '/') . '/news/' . $item['seo_title'] . '.html',
                    'lastmod' => $item['create_date']
                        ? date('c', strtotime($item['create_date']))
                        : date('c'),
                    'priority' => '0.60'
                ];
            }
        }

        return $urls;
    }

    /**
     * Export sitemap and save to file
     * Can be called via cron or admin action
     *
     * @param string $filename Output filename (default: sitemap.xml)
     * @return bool Success status
     */
    public function exportToFile($filename = 'sitemap.xml') {
        // Collect all URLs
        $urls = [];

        // Add homepage
        $urls[] = [
            'loc' => rtrim($this->baseUrl, '/') . '/',
            'lastmod' => date('c'),
            'priority' => '1.00'
        ];

        // Add pages
        $pages = $this->getPages();
        $urls = array_merge($urls, $pages);

        // Generate XML content
        $xml = $this->generateXml($urls);

        // Save to file
        $filepath = SITE_ROOT . '/' . $filename;
        $result = file_put_contents($filepath, $xml);

        if ($result !== false) {
            echo "Sitemap exported successfully to: " . $filepath;
            return true;
        } else {
            echo "Error: Could not write sitemap to file.";
            return false;
        }
    }

    /**
     * Generate XML string from URLs array
     *
     * @param array $urls Array of URL entries
     * @return string XML content
     */
    private function generateXml($urls) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' . "\n";
        $xml .= '        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9' . "\n";
        $xml .= '              http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">' . "\n";

        foreach ($urls as $url) {
            $xml .= "<url>\n";
            $xml .= "  <loc>" . htmlspecialchars($url['loc']) . "</loc>\n";
            $xml .= "  <lastmod>" . $url['lastmod'] . "</lastmod>\n";
            $xml .= "  <priority>" . $url['priority'] . "</priority>\n";
            $xml .= "</url>\n";
        }

        $xml .= "</urlset>\n";

        return $xml;
    }
}
?>
