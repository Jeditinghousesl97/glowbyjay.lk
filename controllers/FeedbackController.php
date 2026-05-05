<?php
/**
 * Feedback Controller
 */
require_once 'models/Feedback.php';
require_once 'helpers/ImageHelper.php';

class FeedbackController extends BaseController
{

    private $model;

    public function __construct()
    {
        $this->model = new Feedback();
    }

    public function index()
    {
        $feedbacks = $this->model->getAll();
        $this->view('admin/feedbacks/index', [
            'title' => 'Feedbacks',
            'feedbacks' => $feedbacks
        ]);
    }

    public function add()
    {
        $this->view('admin/feedbacks/add', [
            'title' => 'Add Reviews'
        ]);
    }

    public function store()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            // Handle Multiple Files
            if (isset($_FILES['images'])) {
                $files = $_FILES['images'];
                $count = count($files['name']);

                for ($i = 0; $i < $count; $i++) {
                    if ($files['error'][$i] == 0) {
                        $storedName = ImageHelper::storeUploadedArrayFile($files, $i, 'feedback_' . $i);
                        if ($storedName !== '') {
                            $this->model->create($storedName);
                        }
                    }
                }
            }

            $this->redirect('feedback/index');
        }
    }

    public function delete($id)
    {
        // Image Hygiene
        $feedback = $this->model->getById($id);
        if ($feedback && !empty($feedback['image_path'])) {
            $this->deleteFile($feedback['image_path']);
        }

        $this->model->delete($id);
        $this->redirect('feedback/index');
    }

}
?>
