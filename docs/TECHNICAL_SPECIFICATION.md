# Yoko Link Checker - Technical Specification

> **Version:** 1.0.7  
> **Last Updated:** January 2025  
> **Audience:** Engineering Team

---

## Table of Contents

1. [Executive Summary](#1-executive-summary)
2. [Problem Statement](#2-problem-statement)
3. [Solution Architecture](#3-solution-architecture)
4. [Key Differentiators](#4-key-differentiators)
5. [Component Deep Dives](#5-component-deep-dives)
6. [Database Schema](#6-database-schema)
7. [Extension Points](#7-extension-points)
8. [Configuration & Constants](#8-configuration--constants)
9. [Development Guide](#9-development-guide)

---

## 1. Executive Summary

Yoko Link Checker is a WordPress plugin designed for **thread-limited hosting environments** (like Kinsta, WP Engine, managed WordPress hosts) where traditional link checkers fail due to PHP worker exhaustion and self-referential HTTP deadlocks.

### Technical Foundation
- **PHP:** 8.0+
- **WordPress:** 6.0+
- **Architecture:** Service Container with Dependency Injection
- **Database:** 3 custom tables with normalized URL deduplication
- **Processing:** AJAX-driven batch processing with cursor-based pagination

---

## 2. Problem Statement

### Why Existing Link Checkers Fail

#### 2.1 PHP Worker Exhaustion (The Core Problem)

Most WordPress sites on managed hosting have **limited PHP workers** (typically 2-4). When a traditional link checker scans internal URLs:

```
[User Request] → PHP Worker 1 → Checks internal URL via HTTP
                                    ↓
                           [HTTP Request to Self]
                                    ↓
                           PHP Worker 2 (occupied)
                                    ↓
                           PHP Worker 1 WAITING...
                                    ↓
                           [DEADLOCK when workers exhausted]
```

**Real-World Impact:**
- 3 PHP workers on Kinsta
- Plugin initiates HTTP request to check `/about-us/` page
- First worker waits for response
- Second worker handles the internal request
- Third worker handles a real user
- **All workers now exhausted**
- Site becomes unresponsive until timeout (30-120 seconds)

This is why sites "hang" or "crash" when running link checkers like Broken Link Checker.

#### 2.2 The Cron Scheduling Problem

Traditional approach:
```php
// Dangerous: Unpredictable execution, often runs during peak traffic
wp_schedule_event(time(), 'hourly', 'check_all_links');
```

Problems:
1. **Peak Traffic Conflicts:** Cron runs regardless of current server load
2. **Long-Running Tasks:** PHP execution time limits kill mid-scan
3. **No Progress Persistence:** Restart from zero after interruption
4. **Blocking Operations:** Synchronous HTTP requests stack up

#### 2.3 Data Model Inefficiency

Most link checkers:
```
Links Table:
| id | url | status | post_id | anchor_text |
| 1  | https://example.com | 200 | 5 | Click here |
| 2  | https://example.com | 200 | 8 | Learn more |
| 3  | https://example.com | 200 | 12 | Example |
```

**Problem:** Same URL stored 3 times. 3 HTTP requests to check the same endpoint.

---

## 3. Solution Architecture

### 3.1 Directory Structure

```
yoko-link-checker/
├── yoko-link-checker.php    # Bootstrap, constants, autoload
├── src/
│   ├── Plugin.php           # Service container (DI orchestration)
│   ├── Activator.php        # Schema creation, capability setup
│   ├── Deactivator.php      # Cleanup, cron removal
│   ├── Admin/               # UI layer
│   │   ├── AdminController.php   # Menu registration, asset loading
│   │   ├── AjaxHandler.php       # AJAX endpoint handlers
│   │   ├── DashboardPage.php     # Stats, scan controls
│   │   ├── ResultsPage.php       # Link listing, export
│   │   └── LinksListTable.php    # WP_List_Table extension
│   ├── Checker/             # URL verification
│   │   ├── UrlChecker.php        # Check orchestration
│   │   ├── HttpClient.php        # WP HTTP API wrapper
│   │   ├── StatusClassifier.php  # HTTP code → status mapping
│   │   └── CheckResult.php       # Value object for results
│   ├── Extractor/           # Link discovery
│   │   ├── ExtractorRegistry.php # Extractor management
│   │   ├── HtmlExtractor.php     # DOM/XPath parsing
│   │   ├── ExtractorInterface.php # Contract
│   │   └── ExtractedLink.php     # Value object
│   ├── Model/               # Domain entities
│   │   ├── Url.php               # Unique URL entity
│   │   ├── Link.php              # Link occurrence entity
│   │   └── Scan.php              # Scan run entity
│   ├── Repository/          # Data access layer
│   │   ├── UrlRepository.php     # URL CRUD with deduplication
│   │   ├── LinkRepository.php    # Link occurrence CRUD
│   │   └── ScanRepository.php    # Scan state CRUD
│   ├── Scanner/             # Scan orchestration
│   │   ├── ScanOrchestrator.php  # Lifecycle management
│   │   ├── BatchProcessor.php    # Batch execution engine
│   │   ├── ContentDiscovery.php  # Post enumeration
│   │   └── ScanState.php         # Progress tracking
│   └── Util/                # Utilities
│       ├── Logger.php            # Debug logging
│       └── UrlNormalizer.php     # URL canonicalization
├── templates/admin/         # PHP view templates
├── assets/{css,js}/         # Admin assets
└── docs/                    # Documentation
```

### 3.2 Design Patterns

| Pattern | Implementation | Purpose |
|---------|----------------|---------|
| **Service Container/DI** | `Plugin::get_service()` | Lazy-loaded dependency resolution |
| **Repository** | `*Repository` classes | Data access abstraction |
| **Registry** | `ExtractorRegistry` | Pluggable extractor management |
| **Strategy** | `ExtractorInterface` | Swappable extraction algorithms |
| **Value Object** | `CheckResult`, `ExtractedLink` | Immutable data transfer |
| **Entity** | `Url`, `Link`, `Scan` | Domain modeling |

### 3.3 Service Container

```php
// Plugin.php - Singleton service container
final class Plugin {
    private static ?Plugin $instance = null;
    private array $services = [];
    
    public static function instance(): Plugin {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function get_service(string $name): object {
        if (!isset($this->services[$name])) {
            $this->services[$name] = $this->create_service($name);
        }
        return $this->services[$name];
    }
    
    private function create_service(string $name): object {
        return match($name) {
            'url_repository'    => new UrlRepository(),
            'link_repository'   => new LinkRepository(),
            'scan_repository'   => new ScanRepository(),
            'url_normalizer'    => new UrlNormalizer(),
            'url_checker'       => new UrlChecker(
                $this->get_service('http_client'),
                $this->get_service('status_classifier')
            ),
            'http_client'       => new HttpClient(),
            'status_classifier' => new StatusClassifier(),
            // ... other services
        };
    }
}

// Usage:
$checker = Plugin::instance()->get_service('url_checker');
```

---

## 4. Key Differentiators

### 4.1 Internal URL Checking via WordPress Functions

**The Innovation:** Instead of making HTTP requests to internal URLs (which consume PHP workers), we use WordPress's own functions to verify content exists.

```php
// BatchProcessor.php - Internal URL resolution
private function check_internal_url(Url $url): CheckResult {
    $url_string = $url->get_url();
    
    // 1. Try url_to_postid() - WordPress's URL → Post ID resolver
    $post_id = url_to_postid($url_string);
    if ($post_id && get_post_status($post_id) === 'publish') {
        return new CheckResult(
            Url::STATUS_OK,
            200,
            'Internal link verified via WordPress',
            $url_string
        );
    }
    
    // 2. Check if it's the homepage
    if (trailingslashit($url_string) === trailingslashit(home_url())) {
        return new CheckResult(Url::STATUS_OK, 200, 'Homepage', $url_string);
    }
    
    // 3. Check taxonomy terms (categories, tags)
    $path = trim(wp_parse_url($url_string, PHP_URL_PATH), '/');
    foreach (['category', 'post_tag'] as $taxonomy) {
        $term = get_term_by('slug', $path, $taxonomy);
        if ($term && !is_wp_error($term)) {
            return new CheckResult(Url::STATUS_OK, 200, 'Taxonomy term', $url_string);
        }
    }
    
    // 4. Check media attachments
    $attachment_id = attachment_url_to_postid($url_string);
    if ($attachment_id) {
        return new CheckResult(Url::STATUS_OK, 200, 'Media attachment', $url_string);
    }
    
    // 5. Fallback: Mark as warning (manual review needed)
    return new CheckResult(
        Url::STATUS_WARNING,
        0,
        'Internal URL could not be verified',
        $url_string
    );
}
```

**Why This Matters:**
- **Zero PHP workers consumed** for internal URL checks
- **No network latency** - direct database queries
- **No self-referential deadlocks** - impossible by design
- **Faster scanning** - database queries vs HTTP round-trips

### 4.2 AJAX-Driven Batch Processing

**Traditional (Problematic):**
```php
// Single long-running cron job
wp_schedule_event(time(), 'hourly', 'scan_all_links');
function scan_all_links() {
    foreach (get_all_posts() as $post) {  // Could be 10,000 posts
        check_links_in_post($post);       // Timeout at 30 seconds
    }
}
```

**Yoko Link Checker (Resilient):**
```javascript
// admin.js - Client-driven polling
class Scanner {
    async runBatch() {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            body: new URLSearchParams({
                action: 'yoko_lc_process_batch',
                _ajax_nonce: nonce
            })
        });
        
        const data = await response.json();
        
        if (data.data.status === 'completed') {
            this.showSuccess();
        } else {
            this.updateProgress(data.data.progress);
            setTimeout(() => this.runBatch(), 1000);  // Next batch
        }
    }
}
```

**Benefits:**
- Each batch is a **separate PHP request** (fresh execution time)
- Progress **persists in database** (resume after interruption)
- User sees **real-time progress** (not a spinning wheel)
- **Natural throttling** via batch delays

### 4.3 Normalized URL Deduplication

**The Problem:**
```
Post 1: <a href="https://example.com">
Post 2: <a href="https://example.com/">
Post 3: <a href="https://EXAMPLE.COM">
Post 4: <a href="https://example.com?utm_source=twitter&utm_medium=social">
Post 5: <a href="https://example.com?utm_medium=social&utm_source=twitter">
```

All five are **the same URL** but would be stored as 5 records by naive implementations.

**Yoko's Approach:**
```php
// UrlNormalizer.php
public function normalize(string $url): string {
    $parsed = parse_url($url);
    
    // Lowercase scheme and host
    $scheme = strtolower($parsed['scheme'] ?? 'http');
    $host = strtolower($parsed['host'] ?? '');
    
    // Remove www. prefix
    $host = preg_replace('/^www\./', '', $host);
    
    // Remove default ports
    $port = ($parsed['port'] ?? null);
    if (($scheme === 'http' && $port == 80) || 
        ($scheme === 'https' && $port == 443)) {
        $port = null;
    }
    
    // Sort query parameters for consistent hashing
    parse_str($parsed['query'] ?? '', $query);
    ksort($query);
    $query_string = http_build_query($query);
    
    // Strip fragments (irrelevant for HTTP)
    // Normalize path (trailing slash consistency)
    
    return $this->assemble($scheme, $host, $port, $path, $query_string);
}

public function hash(string $url): string {
    return hash('sha256', $this->normalize($url));
}
```

**Result:**
```sql
-- URLs table: One record per unique endpoint
| id | url | url_hash | status |
| 1  | https://example.com | a1b2c3... | ok |

-- Links table: Multiple occurrences tracked
| id | url_id | source_id | anchor_text |
| 1  | 1      | 101       | Click here  |
| 2  | 1      | 205       | Learn more  |
| 3  | 1      | 312       | Example     |
```

**Benefits:**
- **One HTTP check per unique URL** (not per occurrence)
- **Consistent status** across all occurrences
- **Accurate reporting** of where links appear

### 4.4 Intelligent Status Classification

```php
// StatusClassifier.php - Handles real-world quirks
class StatusClassifier {
    // Known problematic services
    private const DOMAIN_QUIRKS = [
        'linkedin.com'  => [999 => Url::STATUS_OK],      // LinkedIn returns 999 for valid pages
        'facebook.com'  => [403 => Url::STATUS_WARNING], // FB blocks bots with 403
        'instagram.com' => [429 => Url::STATUS_WARNING], // IG rate limits aggressively
        'twitter.com'   => [400 => Url::STATUS_WARNING], // Twitter/X varies
        'x.com'         => [400 => Url::STATUS_WARNING],
    ];
    
    public function classify(int $http_code, string $url): string {
        $host = parse_url($url, PHP_URL_HOST);
        $host = preg_replace('/^www\./', '', $host);
        
        // Check domain-specific overrides
        foreach (self::DOMAIN_QUIRKS as $domain => $codes) {
            if (str_ends_with($host, $domain) && isset($codes[$http_code])) {
                return $codes[$http_code];
            }
        }
        
        // Standard HTTP code classification
        return match(true) {
            $http_code >= 200 && $http_code < 300 => Url::STATUS_OK,
            $http_code >= 300 && $http_code < 400 => Url::STATUS_REDIRECT,
            $http_code === 403 || $http_code === 401 => Url::STATUS_BLOCKED,
            $http_code === 404 || $http_code === 410 => Url::STATUS_BROKEN,
            $http_code >= 400 && $http_code < 500 => Url::STATUS_WARNING,
            $http_code >= 500 => Url::STATUS_BROKEN,
            $http_code === 0 => Url::STATUS_TIMEOUT,
            default => Url::STATUS_WARNING,
        };
    }
}
```

---

## 5. Component Deep Dives

### 5.1 Scanner System

#### ScanOrchestrator
Manages the scan lifecycle: start → pause → resume → cancel → complete.

```php
class ScanOrchestrator {
    public function start_scan(string $type = 'full'): Scan {
        // 1. Cancel any running scan
        $this->cancel_running_scans();
        
        // 2. Create new scan record
        $scan = new Scan();
        $scan->set_status('running');
        $scan->set_scan_type($type);
        $scan->set_current_phase('discovery');
        $scan->set_started_at(current_time('mysql'));
        
        // 3. Reset URL pending states for re-check
        $this->url_repository->reset_pending_urls();
        
        // 4. Persist and return
        return $this->scan_repository->save($scan);
    }
    
    public function process_batch(): array {
        $scan = $this->get_active_scan();
        
        if ($scan->get_current_phase() === 'discovery') {
            return $this->batch_processor->process_discovery_batch($scan);
        } else {
            return $this->batch_processor->process_checking_batch($scan);
        }
    }
}
```

#### BatchProcessor
Executes individual batches for both phases.

**Discovery Phase:**
```php
public function process_discovery_batch(Scan $scan): array {
    $batch_size = apply_filters('yoko_lc_discovery_batch_size', 50);
    
    // Cursor-based pagination using last_post_id
    $posts = $this->content_discovery->get_posts_to_scan(
        $scan->get_last_post_id(),
        $batch_size
    );
    
    foreach ($posts as $post) {
        // Extract links using registered extractors
        $links = $this->extractor_registry->extract_from($post);
        
        foreach ($links as $extracted) {
            // Normalize and deduplicate URL
            $url = $this->get_or_create_url($extracted->get_url());
            
            // Record link occurrence
            $this->link_repository->create([
                'url_id'      => $url->get_id(),
                'source_id'   => $post->ID,
                'source_type' => $post->post_type,
                'anchor_text' => $extracted->get_anchor_text(),
                'link_context' => $extracted->get_context(),
            ]);
        }
        
        $scan->set_last_post_id($post->ID);
        $scan->increment_processed_posts();
    }
    
    // Check if discovery complete
    if (count($posts) < $batch_size) {
        $scan->set_current_phase('checking');
        $scan->set_total_urls($this->url_repository->count_pending());
    }
    
    return $this->build_progress_response($scan);
}
```

**Checking Phase:**
```php
public function process_checking_batch(Scan $scan): array {
    $batch_size = apply_filters('yoko_lc_checking_batch_size', 5);
    
    $urls = $this->url_repository->get_pending_urls(
        $scan->get_last_url_id(),
        $batch_size
    );
    
    foreach ($urls as $url) {
        if ($url->is_internal()) {
            // WordPress function lookup - no HTTP
            $result = $this->check_internal_url($url);
        } else {
            // External URL - HTTP request
            $result = $this->url_checker->check($url);
        }
        
        // Update URL status
        $url->set_status($result->get_status());
        $url->set_http_code($result->get_http_code());
        $url->set_redirect_url($result->get_redirect_url());
        $url->set_last_checked(current_time('mysql'));
        
        $this->url_repository->save($url);
        $scan->increment_checked_urls();
        $scan->set_last_url_id($url->get_id());
    }
    
    // Check if scan complete
    if (count($urls) < $batch_size) {
        $scan->set_status('completed');
        $scan->set_completed_at(current_time('mysql'));
    }
    
    return $this->build_progress_response($scan);
}
```

### 5.2 Checker System

#### UrlChecker
Orchestrates external URL verification with retry logic.

```php
class UrlChecker {
    public function check(Url $url): CheckResult {
        $use_head = apply_filters('yoko_lc_use_head_request', true);
        
        // Try HEAD first (faster)
        if ($use_head) {
            $result = $this->http_client->head($url->get_url());
            
            // Some servers don't support HEAD, fall back to GET
            if ($result->get_http_code() === 405) {
                $result = $this->http_client->get($url->get_url());
            }
        } else {
            $result = $this->http_client->get($url->get_url());
        }
        
        // Classify the response
        $status = $this->classifier->classify(
            $result->get_http_code(),
            $url->get_url()
        );
        
        return new CheckResult(
            $status,
            $result->get_http_code(),
            $result->get_message(),
            $result->get_redirect_url()
        );
    }
}
```

#### HttpClient
Wraps WordPress HTTP API with sensible defaults.

```php
class HttpClient {
    private const DEFAULT_ARGS = [
        'timeout'     => 15,
        'redirection' => 5,
        'user-agent'  => 'Yoko Link Checker/1.0 (WordPress Plugin)',
        'sslverify'   => true,
    ];
    
    public function head(string $url): HttpResponse {
        $args = apply_filters('yoko_lc_http_request_args', 
            array_merge(self::DEFAULT_ARGS, ['method' => 'HEAD']),
            $url
        );
        
        $response = wp_remote_head($url, $args);
        
        return $this->parse_response($response);
    }
    
    private function parse_response($response): HttpResponse {
        if (is_wp_error($response)) {
            return new HttpResponse(0, $response->get_error_message());
        }
        
        $code = wp_remote_retrieve_response_code($response);
        $redirect = $this->extract_redirect_url($response);
        
        return new HttpResponse($code, '', $redirect);
    }
}
```

### 5.3 Extractor System

#### ExtractorRegistry
Manages pluggable extraction strategies.

```php
class ExtractorRegistry {
    private array $extractors = [];
    
    public function register(string $key, ExtractorInterface $extractor): void {
        $this->extractors[$key] = $extractor;
        do_action('yoko_lc_extractor_registered', $key, $extractor);
    }
    
    public function extract_from(WP_Post $post): array {
        $all_links = [];
        
        foreach ($this->extractors as $extractor) {
            if ($extractor->supports($post)) {
                $links = $extractor->extract($post);
                $all_links = array_merge($all_links, $links);
            }
        }
        
        return $all_links;
    }
}

// Usage - registering custom extractor
add_action('yoko_lc_register_extractors', function($registry) {
    $registry->register('acf', new AcfFieldExtractor());
});
```

#### HtmlExtractor
Parses HTML content for links using DOM.

```php
class HtmlExtractor implements ExtractorInterface {
    public function supports(WP_Post $post): bool {
        return true;  // All posts have content
    }
    
    public function extract(WP_Post $post): array {
        $content = apply_filters('the_content', $post->post_content);
        
        // Suppress libxml errors for malformed HTML
        libxml_use_internal_errors(true);
        
        $doc = new DOMDocument();
        $doc->loadHTML(
            '<?xml encoding="UTF-8">' . $content,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        
        $xpath = new DOMXPath($doc);
        $links = [];
        
        // Extract <a href="..."> links
        foreach ($xpath->query('//a[@href]') as $anchor) {
            $href = $anchor->getAttribute('href');
            $text = trim($anchor->textContent);
            
            // Skip empty, javascript:, mailto:, tel:, #anchors
            if (!$this->is_checkable_url($href)) {
                continue;
            }
            
            // Get surrounding context
            $context = $this->get_context($anchor);
            
            $links[] = new ExtractedLink($href, $text, $context, 'post_content');
        }
        
        // Also extract <img src="..."> for media
        foreach ($xpath->query('//img[@src]') as $img) {
            $src = $img->getAttribute('src');
            $alt = $img->getAttribute('alt');
            
            if ($this->is_checkable_url($src)) {
                $links[] = new ExtractedLink($src, $alt ?: '[image]', '', 'post_content');
            }
        }
        
        libxml_clear_errors();
        
        return $links;
    }
}
```

### 5.4 Repository Layer

#### UrlRepository
Handles URL deduplication via SHA-256 hashing.

```php
class UrlRepository {
    public function get_or_create(string $url, bool $is_internal): Url {
        $hash = $this->normalizer->hash($url);
        
        // Try to find existing URL by hash
        $existing = $this->find_by_hash($hash);
        if ($existing) {
            return $existing;
        }
        
        // Create new URL record
        $url_entity = new Url();
        $url_entity->set_url($url);
        $url_entity->set_url_hash($hash);
        $url_entity->set_is_internal($is_internal);
        $url_entity->set_status(Url::STATUS_PENDING);
        
        return $this->save($url_entity);
    }
    
    public function get_pending_urls(int $after_id, int $limit): array {
        global $wpdb;
        
        $table = $wpdb->prefix . 'yoko_lc_urls';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table}
             WHERE status = %s AND is_ignored = 0 AND id > %d
             ORDER BY id ASC
             LIMIT %d",
            Url::STATUS_PENDING,
            $after_id,
            $limit
        ));
    }
}
```

#### LinkRepository
Tracks link occurrences with source relationships.

```php
class LinkRepository {
    public function get_all_for_export(string $status_filter = ''): array {
        global $wpdb;
        
        $urls_table = $wpdb->prefix . 'yoko_lc_urls';
        $links_table = $wpdb->prefix . 'yoko_lc_links';
        
        $where_clause = '';
        if ($status_filter && $status_filter !== 'all') {
            $where_clause = $wpdb->prepare(' AND u.status = %s', $status_filter);
        }
        
        $results = $wpdb->get_results(
            "SELECT 
                u.url,
                u.status,
                u.http_code,
                u.redirect_url,
                u.last_checked,
                l.source_id,
                l.anchor_text
             FROM {$links_table} l
             JOIN {$urls_table} u ON l.url_id = u.id
             WHERE u.is_ignored = 0 {$where_clause}
             ORDER BY u.status ASC, u.url ASC"
        );
        
        // Enrich with source permalinks
        foreach ($results as $row) {
            $row->source_url = get_permalink($row->source_id);
        }
        
        return $results;
    }
}
```

---

## 6. Database Schema

### 6.1 Tables

#### yoko_lc_urls
Stores unique URLs with normalized hash for deduplication.

```sql
CREATE TABLE {prefix}yoko_lc_urls (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    url             TEXT NOT NULL,
    url_hash        CHAR(64) NOT NULL,           -- SHA-256 of normalized URL
    status          VARCHAR(20) DEFAULT 'pending',
    http_code       SMALLINT UNSIGNED DEFAULT NULL,
    redirect_url    TEXT DEFAULT NULL,
    is_internal     TINYINT(1) DEFAULT 0,
    is_ignored      TINYINT(1) DEFAULT 0,
    last_checked    DATETIME DEFAULT NULL,
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY url_hash (url_hash),
    KEY status (status),
    KEY is_internal (is_internal),
    KEY status_ignored (status, is_ignored)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Status Values:**
- `pending` - Not yet checked
- `ok` - 2xx response
- `redirect` - 3xx response
- `broken` - 4xx/5xx response
- `warning` - Uncertain (403, 401, domain quirks)
- `blocked` - Access denied
- `timeout` - Connection timeout

#### yoko_lc_links
Stores link occurrences (many links to one URL).

```sql
CREATE TABLE {prefix}yoko_lc_links (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    url_id          BIGINT UNSIGNED NOT NULL,     -- FK to yoko_lc_urls
    source_id       BIGINT UNSIGNED NOT NULL,     -- WordPress post ID
    source_type     VARCHAR(50) NOT NULL,         -- post, page, product, etc.
    source_field    VARCHAR(50) DEFAULT 'post_content',
    anchor_text     TEXT DEFAULT NULL,
    link_context    TEXT DEFAULT NULL,            -- Surrounding text
    link_position   INT UNSIGNED DEFAULT NULL,    -- Position in content
    created_at      DATETIME NOT NULL,
    updated_at      DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY url_id (url_id),
    KEY source_id (source_id),
    KEY source_composite (source_id, source_type, source_field)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

#### yoko_lc_scans
Stores scan run metadata for progress tracking.

```sql
CREATE TABLE {prefix}yoko_lc_scans (
    id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    status          VARCHAR(20) DEFAULT 'pending',
    scan_type       VARCHAR(30) DEFAULT 'full',
    started_at      DATETIME DEFAULT NULL,
    completed_at    DATETIME DEFAULT NULL,
    total_posts     INT UNSIGNED DEFAULT 0,
    processed_posts INT UNSIGNED DEFAULT 0,
    total_urls      INT UNSIGNED DEFAULT 0,
    checked_urls    INT UNSIGNED DEFAULT 0,
    last_post_id    BIGINT UNSIGNED DEFAULT 0,   -- Cursor for discovery
    last_url_id     BIGINT UNSIGNED DEFAULT 0,   -- Cursor for checking
    current_phase   VARCHAR(30) DEFAULT 'discovery',
    error_message   TEXT DEFAULT NULL,
    options         TEXT DEFAULT NULL,            -- JSON configuration
    PRIMARY KEY (id),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 6.2 Entity Relationship

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   wp_posts  │       │ yoko_lc_    │       │ yoko_lc_    │
│             │◄──────│   links     │──────►│    urls     │
│  (source)   │ 1   N │             │ N   1 │  (unique)   │
└─────────────┘       └─────────────┘       └─────────────┘
       ▲
       │
       │ Post enumeration
       │
┌──────┴──────┐
│ yoko_lc_    │
│   scans     │
│  (cursor)   │
└─────────────┘
```

---

## 7. Extension Points

### 7.1 Filters

```php
// Customize scannable content
add_filter('yoko_lc_scannable_post_types', function($types) {
    return ['post', 'page', 'product', 'portfolio'];
});

add_filter('yoko_lc_scannable_post_statuses', function($statuses) {
    return ['publish', 'draft'];  // Include drafts
});

// Skip specific posts
add_filter('yoko_lc_skip_post', function($skip, $post) {
    // Skip password-protected posts
    return !empty($post->post_password);
}, 10, 2);

// Skip specific URLs
add_filter('yoko_lc_skip_url_check', function($skip, $url) {
    // Skip localhost/internal dev URLs
    return strpos($url, 'localhost') !== false;
}, 10, 2);

// Batch processing tuning
add_filter('yoko_lc_discovery_batch_size', fn() => 100);  // More posts per batch
add_filter('yoko_lc_checking_batch_size', fn() => 10);    // More URLs per batch
add_filter('yoko_lc_batch_delay', fn() => 2);             // 2 second delay

// HTTP request customization
add_filter('yoko_lc_http_request_args', function($args, $url) {
    // Use custom user agent for specific domains
    if (strpos($url, 'internal-api.com') !== false) {
        $args['headers']['Authorization'] = 'Bearer ' . MY_API_TOKEN;
    }
    return $args;
}, 10, 2);

// Override status classification
add_filter('yoko_lc_classify_status', function($status, $http_code, $url) {
    // Treat 403 from our CDN as OK
    if ($http_code === 403 && strpos($url, 'cdn.oursite.com') !== false) {
        return 'ok';
    }
    return $status;
}, 10, 3);
```

### 7.2 Actions

```php
// Register custom extractors
add_action('yoko_lc_register_extractors', function($registry) {
    // ACF field extractor
    $registry->register('acf', new class implements ExtractorInterface {
        public function supports(WP_Post $post): bool {
            return function_exists('get_fields');
        }
        
        public function extract(WP_Post $post): array {
            $links = [];
            $fields = get_fields($post->ID);
            $this->extract_from_fields($fields, $links);
            return $links;
        }
        
        private function extract_from_fields($fields, &$links) {
            foreach ($fields as $field) {
                if (is_string($field) && filter_var($field, FILTER_VALIDATE_URL)) {
                    $links[] = new ExtractedLink($field, '', '', 'acf_field');
                } elseif (is_array($field)) {
                    $this->extract_from_fields($field, $links);
                }
            }
        }
    });
});

// Scan lifecycle hooks
add_action('yoko_lc_scan_started', function($scan) {
    // Notify Slack
    wp_remote_post(SLACK_WEBHOOK, [
        'body' => json_encode(['text' => 'Link scan started']),
    ]);
});

add_action('yoko_lc_scan_completed', function($scan) {
    $broken = $scan->get_broken_count();
    if ($broken > 0) {
        // Send email notification
        wp_mail(
            get_option('admin_email'),
            "Link Scan Complete: {$broken} broken links found",
            "View results: " . admin_url('admin.php?page=yoko-link-checker-results')
        );
    }
});

// Per-URL hook for integrations
add_action('yoko_lc_url_checked', function($url, $result) {
    // Log broken links to external service
    if ($result->get_status() === 'broken') {
        ErrorTracker::log('broken_link', [
            'url' => $url->get_url(),
            'http_code' => $result->get_http_code(),
        ]);
    }
}, 10, 2);
```

---

## 8. Configuration & Constants

### 8.1 Plugin Constants

```php
// Defined in yoko-link-checker.php
define('YOKO_LC_VERSION', '1.0.7');
define('YOKO_LC_PLUGIN_FILE', __FILE__);
define('YOKO_LC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YOKO_LC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YOKO_LC_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Optional: Enable debug logging (wp-config.php)
define('YOKO_LC_DEBUG', true);
```

### 8.2 WordPress Options

```php
// Stored in wp_options
'yoko_lc_settings' => [
    'enabled_post_types' => ['post', 'page'],
    'check_images'       => true,
    'check_external'     => true,
    'timeout'            => 15,
    'user_agent'         => 'Yoko Link Checker',
],

'yoko_lc_schema_version'        => '1.0.0',
'yoko_lc_activated_at'          => '2025-01-15 10:30:00',
'yoko_lc_remove_data_on_uninstall' => false,
```

### 8.3 Capabilities

```php
// Assigned to administrator role on activation
'yoko_lc_view_results'    // View link reports
'yoko_lc_manage_settings' // Change plugin settings
'yoko_lc_manage_scans'    // Start/stop/cancel scans
```

---

## 9. Development Guide

### 9.1 Local Setup

```bash
# Clone repository
git clone git@github.com:your-org/yoko-link-checker.git
cd yoko-link-checker

# Install dependencies
composer install

# Run code standards check
./vendor/bin/phpcs

# Auto-fix code standards
./vendor/bin/phpcbf
```

### 9.2 Debugging

```php
// Enable debug logging in wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('YOKO_LC_DEBUG', true);

// Logs written to wp-content/debug.log
```

**Log Output Example:**
```
[2025-01-15 10:30:00] YOKO_LC.DEBUG: Processing batch {"phase":"discovery","batch_size":50}
[2025-01-15 10:30:01] YOKO_LC.DEBUG: Extracted 15 links from post 123
[2025-01-15 10:30:02] YOKO_LC.DEBUG: URL check result {"url":"https://example.com","status":"ok","http_code":200}
```

### 9.3 Testing Considerations

1. **Worker Exhaustion Test:** On a 3-worker host, start a scan with 100+ internal URLs. Previous implementations would hang; Yoko completes successfully.

2. **Resume Test:** Start a scan, manually kill PHP mid-batch. Refresh page, click "Resume" — scan continues from cursor position.

3. **Deduplication Test:** Create 5 posts linking to the same URL with different capitalizations. Verify only 1 HTTP check occurs.

4. **Rate Limiting Test:** Add 50 LinkedIn URLs. Verify they're classified as `ok` despite 999 response code.

### 9.4 AJAX Action Reference

| Action | Handler | Purpose |
|--------|---------|---------|
| `yoko_lc_start_scan` | `AjaxHandler::start_scan()` | Initiate new scan |
| `yoko_lc_process_batch` | `AjaxHandler::process_batch()` | Execute next batch |
| `yoko_lc_pause_scan` | `AjaxHandler::pause_scan()` | Pause running scan |
| `yoko_lc_resume_scan` | `AjaxHandler::resume_scan()` | Resume paused scan |
| `yoko_lc_cancel_scan` | `AjaxHandler::cancel_scan()` | Cancel and reset |
| `yoko_lc_get_status` | `AjaxHandler::get_status()` | Fetch current status |
| `yoko_lc_ignore_link` | `AjaxHandler::ignore_link()` | Mark link as ignored |
| `yoko_lc_unignore_link` | `AjaxHandler::unignore_link()` | Remove ignore flag |
| `yoko_lc_clear_data` | `AjaxHandler::clear_data()` | Truncate all data |

---

## Appendix: Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.7 | Jan 2025 | CSV export with source URLs, Reports page rename, filter tabs fix |
| 1.0.6 | Jan 2025 | Bulk action TypeError fix |
| 1.0.5 | Jan 2025 | Status tooltips with descriptions |
| 1.0.4 | Jan 2025 | Clear All Data, styling improvements |
| 1.0.3 | Jan 2025 | Internal URL WordPress-native checking |
| 1.0.2 | Jan 2025 | Debug logging control, redirect_url fix |
| 1.0.1 | Jan 2025 | AJAX prefix fix, namespace correction |
| 1.0.0 | Jan 2025 | Initial release |

---

*Document maintained by Engineering Team. Last updated: January 2025.*
