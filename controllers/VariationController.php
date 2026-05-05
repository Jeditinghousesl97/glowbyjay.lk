<?php
/**
 * Variation Controller
 */
require_once 'models/Variation.php';

class VariationController extends BaseController
{

    private $model;

    public function __construct()
    {
        $this->model = new Variation();
    }

    public function index()
    {
        $variations = $this->model->getAll();
        $this->view('admin/variations/index', [
            'title' => 'Variations',
            'variations' => $variations
        ]);
    }

    public function add()
    {
        $this->view('admin/variations/form', [
            'title' => 'Create Variation',
            'mode' => 'add'
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['name'] ?? '';
            $values = $_POST['values'] ?? []; // Array of strings

            if (!empty($name) && !empty($values)) {
                if ($this->model->createWithValues($name, $values)) {
                    $this->redirect('variation/index');
                } else {
                    echo "Error creating variation.";
                }
            } else {
                echo "Attribute name and at least one value required.";
            }
        }
    }

    public function edit($id)
    {
        $variation = $this->model->getById($id);
        if ($variation) {
            $this->view('admin/variations/edit', [
                'title' => 'Edit Variation',
                'variation' => $variation
            ]);
        } else {
            $this->redirect('variation/index');
        }
    }

    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'] ?? '';
            $values = $_POST['values'] ?? [];

            if ($id && !empty($name) && !empty($values)) {
                if ($this->model->updateWithValues($id, $name, $values)) {
                    $this->redirect('variation/index');
                } else {
                    echo "Error updating variation.";
                }
            } else {
                echo "Attribute name and at least one value required.";
            }
        }
    }

    public function delete($id)
    {
        $this->model->delete($id);
        $this->redirect('variation/index');
    }
    /**
     * API: Get Variations as JSON
     */
    public function get_json()
    {
        header('Content-Type: application/json');
        $variations = $this->model->getAll();
        echo json_encode($variations);
        exit;
    }

}
?>