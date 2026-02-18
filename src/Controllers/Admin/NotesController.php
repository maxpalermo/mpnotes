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
use Symfony\Component\HttpFoundation\JsonResponse;

class NotesController extends FrameworkBundleAdminController
{
    /**
     * Main index action
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        return $this->render('@Modules/mpnotes/views/templates/admin/Note/index.html.twig', [
            'layoutTitle' => $this->trans('Notes Manager', 'Modules.Mpnotes.Admin'),
        ]);
    }

    /**
     * Get note action
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getNote(Request $request)
    {
        $id_row = $request->request->get('id_row');
        $id_note_type = $request->request->get('id_note_type');
        $id_order = $request->request->get('id_order');
        $id_customer = $request->request->get('id_customer');
        $id_employee = $request->request->get('id_employee');

        // Return a simple response for now
        return new JsonResponse([
            'success' => true,
            'message' => 'Note retrieved successfully',
            'data' => [
                'id_row' => $id_row,
                'id_note_type' => $id_note_type,
                'id_order' => $id_order,
                'id_customer' => $id_customer,
                'id_employee' => $id_employee,
            ],
        ]);
    }
}
