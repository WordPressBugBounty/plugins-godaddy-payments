<?php
/**
 * Poynt — a GoDaddy Brand for WooCommerce.
 *
 * @author GoDaddy
 * @copyright Copyright (c) 2021 GoDaddy Operating Company, LLC. All Rights Reserved.
 * @license GPL-2.0
 */

namespace GoDaddy\WooCommerce\Poynt\Emails;

use Exception;
use WC_Email;
use WC_Order;

/**
 * Ready for Pickup Email class.
 */
class ReadyForPickupEmail extends WC_Email
{
    /** @var string the email ID */
    public $id = 'gdp_ready_for_pickup_email';

    /** @var bool true when the email notification is sent to customers */
    protected $customer_email = true;

    /** @var string the standard template path for email content */
    protected $overwriteTemplatePath = 'gdp/';

    /** @var string the base directory for the template */
    public $template_base = '';

    /** @var string the html template path relative to base */
    public $template_html = 'emails/ready-for-pickup.php';

    /** @var string the plain text template path relative to base */
    public $template_plain = 'emails/plain/ready-for-pickup.php';

    /**
     * Ready for Pickup Email constructor.
     *
     * @throws Exception
     * @since 1.3.0
     */
    public function __construct()
    {
        parent::__construct();

        $this->title = __('Ready for pickup', 'godaddy-payments');
        $this->description = __('Ready for pickup emails are sent to customers when their order is marked as ready for pickup.', 'godaddy-payments');
        $this->template_base = poynt_for_woocommerce()->get_plugin_path().'/templates/woocommerce/';
    }

    /**
     * Gets the email default subject.
     *
     * @NOTE: Woo classes being overridden are in snake_case {JS: 2021-09-06}
     *
     * @since 1.3.0
     */
    public function get_default_subject()
    {
        return __('Your {site_title} order is ready for pickup!', 'godaddy-payments');
    }

    /**
     * Gets the email default heading.
     *
     * @since 1.3.0
     */
    public function get_default_heading()
    {
        return __('Order ready for pickup!', 'godaddy-payments');
    }

    /**
     * Gets the content for the HTML version of the email.
     *
     * @throws Exception
     * @return string|void
     * @since 1.3.0
     */
    public function get_content_html()
    {
        // @NOTE: allow merchants to overwrite the template by placing a copy of it the gdp subfolder inside their themes folder {@sshadid: 2021-12-06}
        return \wc_get_template_html(
            $this->template_html,
            $this->getTemplateData(),
            $this->overwriteTemplatePath,
            $this->template_base
        );
    }

    /**
     * Gets the content for the plain version of the email.
     *
     * @throws Exception
     * @return string|void
     * @since 1.3.0
     */
    public function get_content_plain()
    {
        // @NOTE: allow merchants to overwrite the template by placing a copy of it the gdp subfolder inside their themes folder {@sshadid: 2021-12-06}
        return wc_get_template_html(
            $this->template_plain,
            $this->getTemplateData(true),
            $this->overwriteTemplatePath,
            $this->template_base
        );
    }

    /**
     * Gets the data for the email templates.
     *
     * @since 1.3.0
     *
     * @param bool whether or not this the plain text version of the email
     * @return array
     */
    protected function getTemplateData(bool $plainText = false) : array
    {
        return [
            'order'              => $this->object,
            'email_heading'      => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'sent_to_admin'      => false,
            'plain_text'         => $plainText,
            'email'              => $this,
        ];
    }

    /**
     * Prepares the email and sends the message if enabled.
     *
     * @see WC_Email_Customer_Completed_Order::trigger()
     * @internal
     *
     * @param int $orderId the order ID
     * @param WC_Order|false $wcOrder WC order object
     * @throws Exception
     */
    public function trigger(int $orderId, $wcOrder = false)
    {
        $this->setup_locale();

        if ($orderId && ! is_a($wcOrder, 'WC_Order')) {
            if (is_numeric($orderId)) {
                $wcOrder = wc_get_order($orderId);
            }
        }

        if (is_a($wcOrder, 'WC_Order')) {
            $this->object = $wcOrder;
            $this->recipient = $this->object->get_billing_email();
            $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ($this->is_enabled() && $this->get_recipient()) {
            $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
        }

        $this->restore_locale();
    }
}
