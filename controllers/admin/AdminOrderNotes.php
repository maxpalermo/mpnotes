<?php
/**
 * AdminOrderNotesController
 */

namespace MpNotes\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class AdminOrderNotesController extends FrameworkBundleAdminController
{
    public function indexAction()
    {
        return $this->render('@Modules/mpnotes/views/templates/admin/order_notes.html.twig');
    }
}
