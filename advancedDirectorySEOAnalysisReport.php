<?php
/**
 * Advanced Directory-Based SEO Compliance Analysis Script
 * Scans .html and .php files within its own directory and subdirectories.
 */

// Error Display Configuration (Essential for debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Increase memory limit for large directories
ini_set('memory_limit', '256M');

// --- Configuration ---
$target_directory = __DIR__;
$file_extensions = ['html', 'php'];
$keyword_to_track = 'seo'; // Target keyword for density check

// SEO Glossary (For explanation of terms in the report)
$seo_glossary = [
    'title_tag' => 'Specifies the title of the webpage. It is the most critical element displayed on the Search Engine Results Page (SERP). Ideally, it should be between 30 and 60 characters long.',
    'meta_description' => 'Provides a brief summary of the page content. It appears under the title on the SERP. While not a direct ranking factor, it significantly impacts the Click-Through Rate (CTR). The optimal length is between 50 and 160 characters.',
    'h1_tag' => 'Represents the main heading of the page. A page should typically have only one H1 tag, and it should accurately describe the page\'s primary topic.',
    'image_alt_attribute' => 'Describes the purpose of an image to search engines and visually impaired users. It must be present for all relevant images for accessibility and image search optimization.',
    'canonical_link' => 'Informs search engines which URL is the "master" version of a page when duplicate content exists across multiple URLs. This helps consolidate link equity (SEO power) to the preferred page.',
    'min_word_count' => 'The minimum number of words required for the content to be considered comprehensive and in-depth. Longer content generally has a greater potential to rank well.',
    'keyword_density' => 'The ratio of the target keyword (in our case, **' . $keyword_to_track . '**), to the total word count. Excessive density, known as **Keyword Stuffing**, can lead to search engine penalties.',
    'mobile_viewport' => 'A meta tag that instructs the browser on how to adjust the page\'s dimensions and scaling for proper viewing on mobile devices. Mobile-friendliness is a recognized ranking factor.',
    'file_size' => 'The size of the page file in kilobytes. A smaller file size improves page load speed (Page Speed) and contributes to a better user experience.',
];

// Simple SEO Criteria and Scoring
$seo_criteria = [
    'title_tag' => ['score' => 15, 'regex' => '/<title>(.*?)<\/title>/is', 'min_length' => 10, 'max_length' => 65],
    'meta_description' => ['score' => 15, 'regex' => '/<meta.*?name=["\']description["\'].*?content=["\'](.*?)["\'].*?\/?>/is', 'min_length' => 50, 'max_length' => 160],
    'h1_tag' => ['score' => 10, 'regex' => '/<h1[^>]*>(.*?)<\/h1>/is', 'max_count' => 1], 
    'image_alt_attribute' => ['score' => 10, 'regex' => '/<img[^>]*alt=["\'][^"\']*["\'][^>]*>/is', 'min_count' => 1],
    'canonical_link' => ['score' => 10, 'regex' => '/<link.*?rel=["\']canonical["\'].*?\/?>/is'],
    'mobile_viewport' => ['score' => 10, 'regex' => '/<meta.*?name=["\']viewport["\'].*?content=["\']width=device-width, initial-scale=1.*?\/?>/is'],
    'min_word_count' => ['score' => 15, 'min_words' => 300], 
    'keyword_density' => ['score' => 15, 'keyword' => $keyword_to_track, 'min_density' => 0.5, 'max_density' => 2.0],
];
$max_score = array_sum(array_column($seo_criteria, 'score'));

// --- Functions ---

/**
 * Scans the specified directory and its subdirectories for files.
 */
function scan_directory($dir, $extensions) {
    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        if ($item->isFile()) {
            $ext = strtolower($item->getExtension());
            if (in_array($ext, $extensions)) {
                $files[] = $item->getRealPath();
            }
        }
    }
    return $files;
}

/**
 * Reads file content and performs advanced SEO analysis.
 */
function analyze_file($filepath, $criteria, $max_score, $keyword_to_track) {
    $content = file_get_contents($filepath);
    $current_score = 0;
    $analysis_details = [];
    $file_size = round(filesize($filepath) / 1024, 2); 

    // Get plain text content
    $text_content = strip_tags($content);
    $word_count = str_word_count($text_content);

    foreach ($criteria as $key => $crit) {
        $found = false;
        $score_add = 0;
        $message = '';
        $success_flag = true;

        if (isset($crit['regex'])) {
            $match_count = preg_match_all($crit['regex'], $content, $matches);
            $found_content = isset($matches[1][0]) ? trim($matches[1][0]) : '';
            $found = $match_count > 0;

            if ($key === 'h1_tag') {
                if ($match_count === 1) {
                    $score_add = $crit['score'];
                    $message = "A single H1 tag was found. (Title: " . htmlspecialchars($found_content) . ")";
                } else {
                    $success_flag = false;
                    $message = $match_count > 1 ? "Multiple H1 tags found ({$match_count} count). This is not recommended." : "No H1 tag was found.";
                }
            } elseif ($key === 'image_alt_attribute') {
                if ($match_count >= $crit['min_count']) {
                    $score_add = $crit['score'];
                    $message = "At least one image with an alt attribute was found ({$match_count} alt attributes detected).";
                } else {
                    $success_flag = false;
                    $message = "No images with an alt attribute were found.";
                }
            } elseif ($key === 'title_tag' || $key === 'meta_description') {
                $length = strlen($found_content);
                if ($found && $length >= $crit['min_length'] && $length <= $crit['max_length']) {
                    $score_add = $crit['score'];
                    $message = "Found and within the ideal length ({$length} characters). (Value: " . htmlspecialchars($found_content) . ")";
                } elseif ($found) {
                    $success_flag = false;
                    $message = $length < $crit['min_length'] ? "Too short ({$length} characters). Minimum {$crit['min_length']} characters required." : "Too long ({$length} characters). Maximum {$crit['max_length']} characters allowed.";
                } else {
                    $success_flag = false;
                    $message = 'Not found.';
                }
            } elseif ($found) {
                 $score_add = $crit['score'];
                 $message = "Found.";
            } else {
                $success_flag = false;
                $message = 'Not found.';
            }

        } elseif ($key === 'min_word_count') {
            if ($word_count >= $crit['min_words']) {
                $score_add = $crit['score'];
                $message = "Content is of sufficient length ({$word_count} words).";
            } else {
                $success_flag = false;
                $message = "Content is too short: Only {$word_count} words (Minimum: {$crit['min_words']}).";
            }
        } elseif ($key === 'keyword_density') {
            $keyword_count = substr_count(strtolower($text_content), strtolower($keyword_to_track));
            $density = ($word_count > 0) ? ($keyword_count / $word_count) * 100 : 0;

            if ($density >= $crit['min_density'] && $density <= $crit['max_density']) {
                $score_add = $crit['score'];
                $message = "Density is within the ideal range: " . round($density, 2) . "% ({$keyword_count} occurrences).";
            } else {
                $success_flag = false;
                $message = "Density is outside the ideal range: " . round($density, 2) . "%. ";
                if ($density < $crit['min_density']) $message .= "Too low. Use the keyword '{$keyword_to_track}' more frequently.";
                if ($density > $crit['max_density']) $message .= "Too high (potential **Keyword Stuffing**). Reduce density.";
            }
        }

        $current_score += $score_add;
        $analysis_details[$key] = [
            'success' => $success_flag,
            'score_contribution' => $score_add,
            'message' => $message,
            'name' => ucwords(str_replace('_', ' ', $key))
        ];
    }
    
    // Additional Information: File Size
    $analysis_details['file_size'] = [
        'success' => $file_size < 100, // Considered good if under 100KB
        'score_contribution' => 0,
        'message' => "File size: **{$file_size} KB**. (Note: High size can lead to slow loading.)",
        'name' => 'File Size'
    ];

    $percentage_score = ($current_score / $max_score) * 100;
    return [
        'score' => round($percentage_score, 2),
        'details' => $analysis_details,
    ];
}

// --- HTML Reporting Start ---
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Advanced PHP SEO Analysis Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f9; }
        .container { max-width: 1000px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); }
        h1, h2 { color: #333; border-bottom: 2px solid #ccc; padding-bottom: 10px; margin-top: 30px; }
        .file-report { margin-top: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px; background-color: #f9f9f9; }
        .file-title { background-color: #007bff; color: white; padding: 10px; border-radius: 5px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
        .score { font-weight: bold; font-size: 1.2em; }
        .details { margin-top: 10px; padding: 10px; border-top: 1px solid #eee; display: none; }
        .detail-item { margin-bottom: 5px; padding: 5px; border-left: 3px solid; }
        .success { border-left-color: #28a745; background-color: #d4edda; color: #155724; }
        .fail { border-left-color: #dc3545; background-color: #f8d7da; color: #721c24; }
        .info { border-left-color: #ffc107; background-color: #fff3cd; color: #856404; }
        .toggle-btn::after { content: ' ▼'; }
        .file-title[aria-expanded="true"] .toggle-btn::after { content: ' ▲'; }
        .glossary-item { margin-bottom: 15px; padding: 10px; border: 1px solid #eee; border-radius: 4px;}
        .glossary-item strong { color: #007bff; }
    </style>
</head>
<body>

<div class="container">
    <h1>Advanced Directory SEO Analysis Report</h1>
    <p>The script scans **.html** and **.php** files in the current directory (<code><?php echo htmlspecialchars($target_directory); ?></code>) and its subdirectories. Maximum Score: **<?php echo $max_score; ?>**.</p>

    <hr>

    <h2>Analysis Results</h2>

    <?php
    $files_to_analyze = scan_directory($target_directory, $file_extensions);

    if (empty($files_to_analyze)) {
        echo '<p>No (.html or .php) files were found in the scanning directory.</p>';
    } else {
        foreach ($files_to_analyze as $filepath) {
            $filename = str_replace($target_directory, '', $filepath);
            $analysis_result = analyze_file($filepath, $seo_criteria, $max_score, $keyword_to_track);
            $score_color = ($analysis_result['score'] > 75) ? '#28a745' : (($analysis_result['score'] > 50) ? '#ffc107' : '#dc3545');

            // Dropdown Report Header
            echo '<div class="file-report">';
            echo '<div class="file-title" onclick="toggleDetails(\'details-' . md5($filepath) . '\', this)" aria-expanded="false" style="background-color: ' . $score_color . '">';
            echo '<span>File: ' . htmlspecialchars($filename) . '</span>';
            echo '<span class="score">Score: ' . $analysis_result['score'] . '% <span class="toggle-btn"></span></span>';
            echo '</div>';

            // Dropdown Analysis Details
            echo '<div class="details" id="details-' . md5($filepath) . '">';
            foreach ($analysis_result['details'] as $key => $detail) {
                // File Size is an info-only item, not a scoring criterion
                if ($key === 'file_size') {
                    $class = 'info';
                } else {
                    $class = $detail['success'] ? 'success' : 'fail';
                }
                
                $score_text = $key !== 'file_size' ? " (+" . $detail['score_contribution'] . " Score)" : "";

                echo '<div class="detail-item ' . $class . '">';
                echo '<strong>' . htmlspecialchars($detail['name']) . '</strong>' . $score_text . ': ' . $detail['message'];
                echo '</div>';
            }
            echo '</div>'; // .details
            echo '</div>'; // .file-report
        }
    }
    ?>

    <hr>

    <h2>SEO Criteria Glossary (Explanation of Terms)</h2>
    <p>The terms below are the fundamental criteria used in the report to determine your page's SEO compliance.</p>
    
    <?php foreach ($seo_glossary as $term_key => $description): ?>
        <div class="glossary-item">
            <strong><?php echo ucwords(str_replace('_', ' ', $term_key)); ?>:</strong>
            <?php echo htmlspecialchars($description); ?>
        </div>
    <?php endforeach; ?>

</div>

<script>
    function toggleDetails(id, headerElement) {
        var detailsElement = document.getElementById(id);
        var isExpanded = headerElement.getAttribute('aria-expanded') === 'true';

        if (isExpanded) {
            detailsElement.style.display = 'none';
            headerElement.setAttribute('aria-expanded', 'false');
        } else {
            detailsElement.style.display = 'block';
            headerElement.setAttribute('aria-expanded', 'true');
        }
    }
</script>

</body>
</html>