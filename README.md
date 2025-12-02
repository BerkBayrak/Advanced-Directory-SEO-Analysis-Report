#  Advanced Directory SEO Analysis Script
This PHP script is designed to perform a static analysis of HTML and PHP files within a local directory and its subdirectories to assess their basic Search Engine Optimization (SEO) compliance. It generates a detailed HTML report, assigning a score to each file based on the implementation of core on-page SEO factors.

##  Features
* **Recursive Directory Scanning:** Automatically discovers and analyzes `.html` and `.php` files in the execution directory and all subfolders.
* **Weighted Scoring:** Assigns a weighted score out of a maximum possible score based on compliance with defined SEO criteria.
* **Detailed Dropdown Report:** Provides file-specific analysis with expandable sections detailing success/failure for each criterion.
* **Key SEO Criteria Check:** Verifies critical on-page elements, including length constraints and required tag counts.
* **Integrated Glossary:** Includes an SEO glossary to explain the importance of each checked criterion.

## Core SEO Criteria Evaluated
The analysis assigns scores based on the following weighted criteria. The total maximum score is configurable within the script.
| Criterion | Description | Pass Conditions |
| :--- | :--- | :--- |
| **Title Tag** | Presence and length of the `<title>` tag. | Must be present and between **10 and 65 characters**. |
| **Meta Description** | Presence and optimal length of the `description` meta tag. | Must be present and between **50 and 160 characters**. |
| **H1 Tag** | Structure and count of the primary heading. | Must be present, and **only one** H1 tag is allowed per page. |
| **Image Alt Attribute** | Optimization for accessibility and image search. | At least **one** image with a non-empty `alt` attribute must be found. |
| **Canonical Link** | Addressing potential duplicate content issues. | The presence of the `<link rel="canonical"...>` tag in the `<head>`. |
| **Mobile Viewport** | Essential for mobile-friendliness ranking. | The presence of the required `<meta name="viewport"...>` tag. |
| **Minimum Word Count**| Ensuring content depth and quality. | Total text content must exceed **300 words**. |
| **Keyword Density** | Preventing keyword stuffing for a target keyword (`seo` by default). | Density must fall between the configurable **0.5% and 2.0%** range. |

## Configuration Parameters
You can modify the following parameters at the beginning of the `seo_analiz.php` file:

```php
// --- Configuration ---
$target_directory = __DIR__;         // The starting directory for the scan
$keyword_to_track = 'seo';           // The primary keyword for density calculation
$seo_criteria = [
    // Modify scoring and length constraints here
    'title_tag' => ['score' => 15, 'min_length' => 10, 'max_length' => 65],
    // ... other criteria
];
