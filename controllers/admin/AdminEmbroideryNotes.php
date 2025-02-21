<?php
/**
 * AdminEmbroideryNotesController
 */

namespace MpNotes\Controller\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;

class AdminEmbroideryNotesController extends FrameworkBundleAdminController
{
    public function indexAction()
    {
        return $this->render('@Modules/mpnotes/views/templates/admin/embroidery_notes.html.twig');
    }
}
