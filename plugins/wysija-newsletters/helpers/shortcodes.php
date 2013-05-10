<?php
defined('WYSIJA') or die('Restricted access');
class WYSIJA_help_shortcodes extends WYSIJA_object {

    private $email;

    private $receiver;

    private $userM;

    private $find;

    private $replace;
    function WYSIJA_help_shortcodes() {
    }

    private function initialize($email, $receiver) {

        $this->email = $email;
        $this->receiver = $receiver;
        $this->userM =& WYSIJA::get('user','model');
        $this->userM->getFormat = OBJECT;
        $this->find = array();
        $this->replace = array();
    }
    public function replace_body($email, $receiver) {
        $this->initialize($email, $receiver);
        $body_tags = $this->email->tags;
        $this->loop_tags($body_tags);
        $replaced_body = str_replace($this->find, $this->replace, $this->email->body);
        return $replaced_body;
    }
    public function replace_subject($email, $receiver) {
        $this->initialize($email, $receiver);
        $subject_tags = $this->email->subject_tags;
        $this->loop_tags($subject_tags);
        $replaced_subject = str_replace($this->find, $this->replace, $this->email->subject);
        return $replaced_subject;
    }
    private function loop_tags($tags) {
        $this->find = array();
        $this->replace = array();

        foreach($tags as $tag_find => $tag_replace){
            foreach($tag_replace as $couple_value){
                switch ($couple_value[0]) {

                    case 'user':
                        $replacement = $this->replace_user_shortcodes($couple_value[1]);
                        if ($replacement === 'subscriber') {
                            continue;
                        }
                        break(2);
                    case 'default':
                        $replacement = $couple_value[1];
                        break(2);

                    case 'newsletter':
                        $replacement = $this->replace_newsletter_shortcodes($couple_value[1]);
                        break;

                    case 'date':
                        $replacement = $this->replace_date_shortcodes($couple_value[1]);
                        break;

                    case 'global':
                        $replacement = $this->replace_global_shortcodes($couple_value[1]);
                        break;

                    case 'custom':
                        $replacement = $this->replace_custom_shortcodes($couple_value[1]);
                        break;
                    default:
                        break;
                }
            }
            $this->find[] = $tag_find;
            $this->replace[] = $replacement;
            $replacement = '';
        }
    }




    private function replace_user_shortcodes($tag_value) {
        if (($tag_value === 'firstname') || ($tag_value === 'lastname') || ($tag_value === 'email')) {
            if(isset($this->receiver->$tag_value) && $this->receiver->$tag_value) {
                $replacement = $this->receiver->$tag_value;
             } else {
                $replacement = 'subscriber';
             }
        }
        if ($tag_value === 'displayname') {
            $user_info = get_userdata($this->receiver->wpuser_id);
            if($user_info->display_name != false) {
                $replacement = $user_info->display_name;
             } elseif($user_info->user_nicename != false) {
                $replacement = $user_info->user_nicename;
             } else {
                $replacement = 'member';
             }
        }
        return $replacement;
    }



    private function replace_global_shortcodes($tag_value) {
        if (($tag_value === 'unsubscribe')) {
            $replacement = $this->userM->getUnsubLink($this->receiver);
        }
        if ($tag_value === 'manage') {
            $replacement = $this->userM->getEditsubLink($this->receiver);
        }
        if ($tag_value === 'browser') {
            $emailH =& WYSIJA::get('email','helper');
            $configM =& WYSIJA::get('config','model');
            $data_email = array();
            $data_email['email_id'] = $this->email->email_id;
            $view_browser_url = $emailH->getVIB($data_email);
            $view_browser_message = $configM->viewInBrowserLink(true);
            $replacement .= $view_browser_message['pretext'];
            $replacement .= '<a href="' . $view_browser_url . '">';
            $replacement .= $view_browser_message['label'];
            $replacement .= '</a>';
            $replacement .= $view_browser_message['posttext'];
        }
        return $replacement;
    }




    private function replace_newsletter_shortcodes($tag_value) {
        switch ($tag_value) {
            case 'subject':
                $replacement = $this->email->subject;
                break;
            case 'total':
                $replacement = $this->email->params['autonl']['articles']['count'];
                break;
            case 'post_title':
                $replacement = $this->email->params['autonl']['articles']['first_subject'];
                break;
            case 'number':
                $replacement = count($this->email->params['autonl']['articles']['ids']);
                break;
            default:
                break;
        }
        return $replacement;
    }






    private function replace_date_shortcodes($tag_value) {
        $current_time = current_time('timestamp');
        switch ($tag_value) {
            case 'd':
                $replacement = date( 'j', $current_time);
                break;
            case 'm':
                $replacement = date( 'n', $current_time);
                break;
            case 'y':
                $replacement = date( 'Y', $current_time);
                break;
            case 'dtext':
                $replacement = date( 'l', $current_time);
                break;
            case 'mtext':
                $replacement = date( 'F', $current_time);
                break;
            case 'dordinal':
                $replacement = date( 'jS', $current_time);
                break;
            default:
                break;
        }
        return $replacement;
    }
    

    private function replace_custom_shortcodes($tag_value) {
        $replacement = apply_filters('wysija_shortcodes', $tag_value);
        return $replacement;
    }
}