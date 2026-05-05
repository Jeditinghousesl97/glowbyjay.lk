<?php
/**
 * Size Guide Controller
 */
require_once 'models/SizeGuide.php';
require_once 'helpers/ImageHelper.php';

class SizeGuideController extends BaseController
{

    private $model;

    public function __construct()
    {
        $this->model = new SizeGuide();
    }

    public function index()
    {
        $guides = $this->model->getAll();
        $this->view('admin/sizeguides/index', [
            'title' => 'Size Guides',
            'guides' => $guides
        ]);
    }

    public function add()
    {
        $this->view('admin/sizeguides/form', [
            'title' => 'Add Size Guide',
            'mode' => 'add'
        ]);
    }

    public function edit($id)
    {
        $guide = $this->model->getById($id);
        if (!$guide) {
            $this->redirect('sizeGuide/index');
            return;
        }

        $this->view('admin/sizeguides/form', [
            'title' => 'Edit Size Guide',
            'mode' => 'edit',
            'guide' => $guide
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';

            // Image Upload
            $imagePath = isset($_FILES['image']) ? ImageHelper::storeUploadedFile($_FILES['image'], 'sizeguide') : '';

            if (
                $this->model->create([
                    'name' => $name,
                    'image_path' => $imagePath
                ])
            ) {
                $this->redirect('sizeGuide/index');
            } else {
                echo "Error adding size guide.";
            }
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';

            if (!$id) {
                $this->redirect('sizeGuide/index');
                return;
            }

            $imagePath = null;
            if (isset($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $storedName = ImageHelper::storeUploadedFile($_FILES['image'], 'sizeguide');
                if ($storedName !== '') {
                    $imagePath = $storedName;
                    $currentGuide = $this->model->getById($id);
                    if ($currentGuide && !empty($currentGuide['image_path'])) {
                        $this->deleteFile($currentGuide['image_path']);
                    }
                }
            }

            if ($this->model->update($id, [
                'name' => $name,
                'image_path' => $imagePath
            ])) {
                $this->redirect('sizeGuide/index');
            } else {
                echo "Error updating size guide.";
            }
        }
    }

    public function delete($id)
    {
        // Image Hygiene
        $guide = $this->model->getById($id);
        if ($guide && !empty($guide['image_path'])) {
            $this->deleteFile($guide['image_path']);
        }

        $this->model->delete($id);
        $this->redirect('sizeGuide/index');
    }

    /**
     * API: Get Size Guides as JSON
     */
    public function get_json()
    {
        header('Content-Type: application/json');
        $guides = $this->model->getAll();
        echo json_encode($guides);
        exit;
    }

}
?>
