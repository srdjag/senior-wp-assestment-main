<?php

/**
 * Class ArticleVoting
 *
 * Main class for the Article Voting Plugin.
 * Handles initialization and all functionalities.
 *
 * @package ArticleVoting
 */

class ArticleVoting {

    const NONCE_ACTION = 'article_voting_nonce';

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */

    public function __construct() {
        add_action('the_content', array($this, 'appendArticleFeedback'));
        add_action('wp_enqueue_scripts', array($this, 'enqueueAssets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueueAssetsAdmin'));
        add_action('wp_ajax_article_voting_process_feedback', array($this, 'processFeedbackAjax'));
        add_action('wp_ajax_nopriv_article_voting_process_feedback', array($this, 'processFeedbackAjax'));
        add_action('wp_ajax_article_voting_ratio', array($this, 'getVoteRatioAjax'));
        add_action('wp_ajax_nopriv_article_voting_ratio', array($this, 'getVoteRatioAjax'));
        add_action('wp_ajax_user_vote_status', array($this, 'getUserVoteStatus'));
        add_action('wp_ajax_nopriv_user_vote_status', array($this, 'getUserVoteStatus'));
        add_action('add_meta_boxes', array($this, 'addVotingMetaBox'));
    }

	/**
	 * Add Voting Metabox
	 *
	 * Register custom metabox for voting results on post edit page
	 *
	 * @since 1.0.0
	 */

    public function addVotingMetaBox() {
        add_meta_box(
            'article_voting_meta_box',
            'Voting Result',
            array($this, 'renderVotingMetaBox'),
            'post',
            'side',
            'high'
        );
    }

	/**
	 * Render Voting Metabox
	 *
	 * Render the custom voting results metabox
	 *
	 * @since 1.0.0
	 */

    public function renderVotingMetaBox($post) {
        $voteRatio = self::getVoteRatio();
        include plugin_dir_path(__FILE__) . '../templates/meta-box-template.php';
    }

	/**
	 * Process Feedback Ajax
	 *
	 * Process the feedback ajax call from the frontend form
	 *
	 * @since 1.0.0
	 */

    public function processFeedbackAjax() {
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            $feedback = sanitize_text_field($_POST['feedback']);
            $userIP = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            $postID = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

            $existingFeedbacks = get_post_meta($postID, '_article_voting_feedbacks', true);
            $existingFeedbacks = is_array($existingFeedbacks) ? $existingFeedbacks : array();

            $existingFeedbackFromIP = array_filter($existingFeedbacks, function ($item) use ($userIP) {
                return $item['user_ip'] === $userIP;
            });

            if (empty($existingFeedbackFromIP)) {
                $newFeedback = array(
                    'feedback' => $feedback,
                    'user_ip' => $userIP,
                    'timestamp' => current_time('timestamp')
                );

                $existingFeedbacks[] = $newFeedback;

                update_post_meta($postID, '_article_voting_feedbacks', $existingFeedbacks);

                $response = array(
                    'status' => 'success',
                    'message' => 'Feedback received and saved to postmeta',
                    'user_ip' => $userIP,
                    'post_id' => $postID
                );
            } else {
                $response = array(
                    'status' => 'error',
                    'message' => 'Feedback from this user already exists',
                    'user_ip' => $userIP,
                    'post_id' => $postID
                );
            }

            wp_send_json($response);
        } else {
            $response = array('status' => 'error', 'message' => 'Nonce verification failed');
            wp_send_json($response);
        }

        exit();
    }

	/**
	 * Append Article Feedback
	 *
	 * Appends the article feedback form at the end of every post
	 *
	 * @since 1.0.0
	 */

    public function appendArticleFeedback($content) {
        if (is_single()) {
            $formHTML = $this->loadFormTemplate();
            $content .= $formHTML;
        }

        return $content;
    }

	/**
	 * Load form template
	 *
	 * Loads the feedback form template
	 *
	 * @since 1.0.0
	 */

    private function loadFormTemplate() {
        $templatePath = plugin_dir_path(__FILE__) . '../templates/form-template.php';

        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            return ob_get_clean();
        }

        return '';
    }

	/**
	 * Enqueue Admin Assets
	 *
	 * Enqueues admin CSS on post edit page
	 *
	 * @since 1.0.0
	 */

    public function enqueueAssetsAdmin() {
        global $pagenow;

        if ($pagenow === 'post.php' || $pagenow === 'post-new.php') {
            wp_enqueue_style('article-voting-admin-style', plugin_dir_url(__FILE__) . '../assets/style-admin.css');
        }
    }

	/**
	 * Enqueue Assets
	 *
	 * Enqueues JS and CSS
	 *
	 * @since 1.0.0
	 */

    public function enqueueAssets() {
        wp_enqueue_style('article-voting-style', plugin_dir_url(__FILE__) . '../assets/style.css');
        wp_enqueue_script('article-voting-script', plugin_dir_url(__FILE__) . '../assets/script.js', array('jquery'), null, true);
        wp_localize_script('article-voting-script', 'article_voting_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'article_id' => get_the_ID(),
            'nonce'    => wp_create_nonce(self::NONCE_ACTION)
        ));
    }

	/**
	 * Get User Vote Status
	 *
	 * Gets the information whether the user voted or not
	 *
	 * @since 1.0.0
	 */

    public function getUserVoteStatus() {
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            $userIP = sanitize_text_field($_SERVER['REMOTE_ADDR']);
            $articleID = $_POST['post_id'];

            $feedbacks = get_post_meta($articleID, '_article_voting_feedbacks', true);

            if (is_array($feedbacks)) {
                foreach ($feedbacks as $feedback) {
                    if ($feedback['user_ip'] === $userIP) {
                        $userVote = $feedback['feedback'];
                        wp_send_json(['status' => 'success', 'user_vote' => $userVote]);
                    }
                }
            }

            wp_send_json(['status' => 'error', 'message' => 'User has not voted yet.']);
        }
    }

	/**
	 * Get vote Ratio
	 *
	 * Calculates and retrieves post vote ratio in percentages
	 *
	 * @since 1.0.0
	 */

    public function getVoteRatio() {
        $articleID = get_the_ID();

        $existingFeedbacks = get_post_meta($articleID, '_article_voting_feedbacks', true);
        $existingFeedbacks = is_array($existingFeedbacks) ? $existingFeedbacks : array();

        $trueVotes = $falseVotes = 0;

        foreach ($existingFeedbacks as $feedback) {
            if ($feedback['feedback'] === 'true') {
                $trueVotes++;
            } elseif ($feedback['feedback'] === 'false') {
                $falseVotes++;
            }
        }

        $totalVotes = $trueVotes + $falseVotes;
        $ratioTrue = $totalVotes > 0 ? ($trueVotes / $totalVotes) * 100 : 0;
        $ratioFalse = $totalVotes > 0 ? ($falseVotes / $totalVotes) * 100 : 0;

        return array(
            'ratio_true' => $ratioTrue,
            'ratio_false' => $ratioFalse,
        );
    }

	/**
	 * Get Vote Ratio Ajax
	 *
	 * Ajax handler that calculates and retrieves post vote ratio
	 *
	 * @since 1.0.0
	 */
    public function getVoteRatioAjax() {
        if (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], self::NONCE_ACTION)) {
            $articleID = $_POST['articleID'];

            $existingFeedbacks = get_post_meta($articleID, '_article_voting_feedbacks', true);
            $existingFeedbacks = is_array($existingFeedbacks) ? $existingFeedbacks : array();

            $trueVotes = $falseVotes = 0;

            foreach ($existingFeedbacks as $feedback) {
                if ($feedback['feedback'] === 'true') {
                    $trueVotes++;
                } elseif ($feedback['feedback'] === 'false') {
                    $falseVotes++;
                }
            }

            $totalVotes = $trueVotes + $falseVotes;
            $ratioTrue = $totalVotes > 0 ? ($trueVotes / $totalVotes) * 100 : 0;
            $ratioFalse = $totalVotes > 0 ? ($falseVotes / $totalVotes) * 100 : 0;

            wp_send_json(array(
                'status' => 'success',
                'article_id' => $articleID,
                'ratio_true' => $ratioTrue,
                'ratio_false' => $ratioFalse,
            ));
        }
    }
}
