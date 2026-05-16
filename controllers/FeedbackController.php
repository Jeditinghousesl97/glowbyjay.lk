<?php
/**
 * Feedback Controller
 */
require_once 'models/Feedback.php';
require_once 'models/Setting.php';
require_once 'helpers/ImageHelper.php';

class FeedbackController extends BaseController
{

    private $model;
    private $settingModel;

    public function __construct()
    {
        $this->model = new Feedback();
        $this->settingModel = new Setting();
    }

    public function index()
    {
        $feedbacks = $this->sortFeedbacksBySavedOrder($this->model->getAll());
        $this->view('admin/feedbacks/index', [
            'title' => 'Customer Reviews',
            'feedbacks' => $feedbacks
        ]);
    }

    public function add()
    {
        $this->view('admin/feedbacks/add', [
            'title' => 'Add Review Screenshots'
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

            $this->refreshReviewOrder();
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
        $this->removeFromReviewOrder((int) $id);
        $this->redirect('feedback/index');
    }

    public function reorder()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('feedback/index');
            return;
        }

        $postedOrder = trim((string) ($_POST['review_order'] ?? ''));
        if ($postedOrder === '') {
            $this->redirect('feedback/index');
            return;
        }

        $ids = array_values(array_filter(array_map(static function ($item) {
            return (int) trim((string) $item);
        }, explode(',', $postedOrder)), static function ($id) {
            return $id > 0;
        }));

        if (!empty($ids)) {
            $this->settingModel->set('review_slider_order', json_encode(array_values(array_unique($ids))));
        }

        $this->redirect('feedback/index');
    }

    private function sortFeedbacksBySavedOrder(array $feedbacks): array
    {
        $savedOrderRaw = (string) $this->settingModel->get('review_slider_order', '[]');
        $savedOrder = json_decode($savedOrderRaw, true);
        if (!is_array($savedOrder) || empty($savedOrder)) {
            return $feedbacks;
        }

        $positionMap = [];
        foreach ($savedOrder as $index => $id) {
            $positionMap[(int) $id] = (int) $index;
        }

        usort($feedbacks, static function ($a, $b) use ($positionMap) {
            $idA = (int) ($a['id'] ?? 0);
            $idB = (int) ($b['id'] ?? 0);
            $hasA = array_key_exists($idA, $positionMap);
            $hasB = array_key_exists($idB, $positionMap);

            if ($hasA && $hasB) {
                return $positionMap[$idA] <=> $positionMap[$idB];
            }
            if ($hasA) {
                return -1;
            }
            if ($hasB) {
                return 1;
            }

            return strtotime((string) ($b['created_at'] ?? '')) <=> strtotime((string) ($a['created_at'] ?? ''));
        });

        return $feedbacks;
    }

    private function refreshReviewOrder(): void
    {
        $all = $this->model->getAll();
        if (empty($all)) {
            $this->settingModel->set('review_slider_order', '[]');
            return;
        }

        $savedOrderRaw = (string) $this->settingModel->get('review_slider_order', '[]');
        $savedOrder = json_decode($savedOrderRaw, true);
        $savedIds = is_array($savedOrder) ? array_values(array_filter(array_map('intval', $savedOrder), static function ($id) {
            return $id > 0;
        })) : [];

        $allIds = array_map(static function ($row) {
            return (int) ($row['id'] ?? 0);
        }, $all);
        $allIds = array_values(array_filter($allIds, static function ($id) {
            return $id > 0;
        }));

        $existing = [];
        foreach ($savedIds as $id) {
            if (in_array($id, $allIds, true)) {
                $existing[] = $id;
            }
        }

        // Newly added items should appear first.
        $newIds = [];
        foreach ($allIds as $id) {
            if (!in_array($id, $existing, true)) {
                $newIds[] = $id;
            }
        }

        $finalOrder = array_merge($newIds, $existing);
        $this->settingModel->set('review_slider_order', json_encode($finalOrder));
    }

    private function removeFromReviewOrder(int $id): void
    {
        if ($id <= 0) {
            return;
        }
        $savedOrderRaw = (string) $this->settingModel->get('review_slider_order', '[]');
        $savedOrder = json_decode($savedOrderRaw, true);
        if (!is_array($savedOrder)) {
            return;
        }
        $savedOrder = array_values(array_filter(array_map('intval', $savedOrder), static function ($savedId) use ($id) {
            return $savedId > 0 && $savedId !== $id;
        }));
        $this->settingModel->set('review_slider_order', json_encode($savedOrder));
    }

}
?>
