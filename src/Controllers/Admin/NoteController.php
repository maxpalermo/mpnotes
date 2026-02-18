<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpNotes\Controllers\Admin;

use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use MpSoft\MpNotes\Helpers\NotePanel;

class NoteController extends FrameworkBundleAdminController
{
    private $errors = [];
    
    public function indexAction(Request $request)
    {
        $action = $request->request->get('action');
        $params = $request->request->all();

        if ($action && method_exists($this, $action . 'Action')) {
            $method = $action . 'Action';
            return $this->$method($request);
        }

        return $this->render('@MpNotes/Admin/Note/index.html.twig', [
            'errors' => $this->errors,
        ]);
    }

    public function getNoteAction(Request $request)
    {
        $id_row = $request->request->get('id_row');
        $id_note_type = $request->request->get('id_note_type');
        $id_order = $request->request->get('id_order');
        $id_customer = $request->request->get('id_customer');
        $id_employee = $request->request->get('id_employee');

        $html = NotePanel::renderNotePanel($id_row, $id_note_type, $id_order, $id_customer, $id_employee);
        if ($html['success']) {
            return $this->json(['success' => true, 'html' => $html['html']]);
        }

        return $this->json(['success' => false, 'message' => NotePanel::renderAlertEmptyPanel()]);
    }
}
