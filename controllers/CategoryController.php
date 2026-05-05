<?php
/**
 * Category Controller
 */
require_once 'models/Category.php';
require_once 'helpers/ImageHelper.php';

class CategoryController extends BaseController
{

    private $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new Category();
    }

    /**
     * List all categories (The "Selector" Page)
     */
    public function index()
    {
        $categories = $this->categoryModel->getAll();

        // Organize into Tree Structure for View
        $tree = [];
        $keyMap = [];

        // First pass: Keys
        foreach ($categories as $cat) {
            $keyMap[$cat['id']] = $cat;
            $keyMap[$cat['id']]['children'] = [];
        }

        // Second pass: Build Tree
        foreach ($keyMap as $id => &$cat) {
            if ($cat['parent_id']) {
                if (isset($keyMap[$cat['parent_id']])) {
                    $keyMap[$cat['parent_id']]['children'][] = &$cat;
                }
            } else {
                $tree[] = &$cat;
            }
        }

        $this->view('admin/categories/index', [
            'title' => 'Categories',
            'categoryTree' => $tree
        ]);
    }

    /**
     * Show Add Form
     */
    public function add()
    {
        $mainCategories = $this->categoryModel->getMainCategories();
        $this->view('admin/categories/form', [
            'title' => 'Add Category',
            'mode' => 'add',
            'parents' => $mainCategories
        ]);
    }

    /**
     * Handle Creation
     */
    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $type = $_POST['type'] ?? 'main'; // 'main' or 'sub'
            $parent_id = ($type === 'sub') ? ($_POST['parent_id'] ?? null) : null;
            $display_order = isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0;

            // Image Upload Logic (Fixed)
            $imagePath = isset($_FILES['image']) ? ImageHelper::storeUploadedFile($_FILES['image'], 'category') : '';

            if (
                $this->categoryModel->create([
                    'name' => $name,
                    'image' => $imagePath,
                    'parent_id' => $parent_id,
                    'display_order' => $display_order
                ])
            ) {
                $this->redirect('category/index');
            } else {
                echo "Error adding category.";
            }
        }
    }

    /**
     * Show Edit Form
     */
    public function edit()
    {
        // Need ID from URL usually passed via router logic in index.php
        // Our simplified router might need updating to pass params, 
        // BUT currently index.php implementation: call_user_func_array passes params.
        // So edit($id) works if we define it like that.
        // Let's get ID from func_get_args or parameter
        $args = func_get_args();
        $id = $args[0] ?? null;

        if (!$id) {
            $this->redirect('category/index');
            return;
        }

        $category = $this->categoryModel->getById($id);
        $mainCategories = $this->categoryModel->getMainCategories();

        $this->view('admin/categories/form', [
            'title' => 'Edit Category',
            'mode' => 'edit',
            'category' => $category,
            'parents' => $mainCategories
        ]);
    }

    /**
     * Handle Update
     */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = $_POST['id'];
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? 'main';
        $parent_id = ($type === 'sub') ? ($_POST['parent_id'] ?? null) : null;
        $display_order = isset($_POST['display_order']) ? (int) $_POST['display_order'] : 0;

            // Image Logic
            $imagePath = null;
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $storedName = ImageHelper::storeUploadedFile($_FILES['image'], 'category');
                if ($storedName !== '') {
                    $imagePath = $storedName;
                    $currentCat = $this->categoryModel->getById($id);
                    if ($currentCat && !empty($currentCat['image'])) {
                        $this->deleteFile($currentCat['image']);
                    }
                }
            }

            if (
                $this->categoryModel->update($id, [
                    'name' => $name,
                    'image' => $imagePath,
                    'parent_id' => $parent_id,
                    'display_order' => $display_order
                ])
            ) {
                $this->redirect('category/index');
            } else {
                echo "Error updating category.";
            }
        }
    }

    public function delete($id)
    {
        // Image Hygiene
        $category = $this->categoryModel->getById($id);
        if ($category && !empty($category['image'])) {
            $this->deleteFile($category['image']);
        }

        $store = $this->categoryModel->delete($id);
        $this->redirect('category/index');
    }


     /**
     * API: Get Categories as JSON
     */
    public function get_json()
    {
        header('Content-Type: application/json');
        $categories = $this->categoryModel->getAll();
        echo json_encode($categories);
        exit;
    }

}

?>
