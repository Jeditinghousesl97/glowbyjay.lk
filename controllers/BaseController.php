<?php
/**
 * Base Controller
 * 
 * This is the "Parent" class for all other Controllers.
 * Ideally, it holds shared logic like "rendering a view".
 * 
 * Think of this as the basic toolset every controller gets.
 */
class BaseController
{

    /**
     * Render a View
     * 
     * This function is used to load the HTML files (Views).
     * 
     * @param string $viewPath The path to the view file (e.g., 'admin/dashboard')
     * @param array $data Data to pass to the view (e.g., ['title' => 'Dashboard'])
     */
    protected function view($viewPath, $data = [])
    {
        // Extract array keys as variables
        // If we pass ['title' => 'Home'], the view will have a variable $title = 'Home'.
        extract($data);

        $fullPath = 'views/' . $viewPath . '.php';

        if (file_exists($fullPath)) {
            require $fullPath;
        } else {
            echo "Error: View file '$viewPath' not found.";
        }
    }

    /**
     * Redirect
     * Helper to send the user to a new URL.
     */
    protected function redirect($url)
    {
        $target = BASE_URL . ltrim((string) $url, '/');

        // Preferred redirect path
        if (!headers_sent()) {
            header("Location: " . $target);
            exit;
        }

        // Fallback when output was already sent before redirect.
        echo '<script>window.location.href=' . json_encode($target) . ';</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        exit;
    }

    /**
     * Safe File Deletion Helper 
     * Filesystem hygiene to prevent orphan files.
     * 
     * @param string $filename The basename of the file (e.g. '123_abc.jpg')
     */
    protected function deleteFile($filename)
    {
        // Basic Validation
        if (empty($filename)) {
            return;
        }

        require_once ROOT_PATH . 'helpers/ImageHelper.php';
        ImageHelper::deleteImageSet($filename);
    }

}
?>
