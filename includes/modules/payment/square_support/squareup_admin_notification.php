<?php
/**
 * squareup admin display component
 *
 * @package paymentMethod
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: DrByte  June 2017 $
 */

$outputStartBlock = '';
$outputMain       = '';
$outputSquare     = '';
$outputCapt       = '';
$outputVoid       = '';
$outputRefund     = '';
$outputEndBlock   = '';
$output           = '';


$outputStartBlock .= '<td><table class="noprint">' . "\n";
$outputStartBlock .= '<tr style="background-color : #bbbbbb; border-style : dotted;">' . "\n";
$outputEndBlock   .= '</tr>' . "\n";
$outputEndBlock   .= '</table></td>' . "\n";


if (!empty($transaction) && $transaction->getId()) {
    $outputSquare .= '<td valign="top"><table>' . "\n";

    $outputSquare .= '<tr><td class="main">' . "\n";
    $outputSquare .= 'Transaction ID: ' . "\n";
    $outputSquare .= '</td><td class="main">' . "\n";
    $outputSquare .= $transaction->getId() . "\n";
    $outputSquare .= '</td></tr>' . "\n";

    $outputSquare .= '<tr><td class="main">' . "\n";
    $outputSquare .= 'Reference: ' . "\n";
    $outputSquare .= '</td><td class="main">' . "\n";
    $outputSquare .= zen_output_string_protected($transaction->getReferenceId()) . "\n";
    $outputSquare .= '</td></tr>' . "\n";

//    $outputSquare .= '<tr><td class="main">' . "\n";
//    $outputSquare .= 'Square Service: ' . "\n";
//    $outputSquare .= '</td><td class="main">' . "\n";
//    $outputSquare .= $transaction->getProduct() . "\n";
//    $outputSquare .= '</td></tr>' . "\n";

    $outputSquare .= '<tr><td class="main">' . "\n";
    $outputSquare .= '<strong>Payments Tendered: </strong>' . "\n";
    $outputSquare .= '</td><td class="main">&nbsp;</td></tr>' . "\n";
    $payments     = $transaction->getTenders();
    foreach ($payments as $payment) {
        $currency_code = $payment->getAmountMoney()->getCurrency();
        $amount = $currencies->format($payment->getAmountMoney()->getAmount() / (pow(10, $currencies->get_decimal_places($currency_code))), false, $currency_code) ;
        $outputSquare .= '<tr><td class="main">' . "\n";
        $outputSquare .= $payment->getCreatedAt() . "\n<br>" . $payment->getId();
        $outputSquare .= '</td><td class="main">' . "\n";
        $outputSquare .= $amount . ' ' . $currency_code . "\n";
        if ($payment->getNote()) $outputSquare .= '<br>' . nl2br(zen_output_string_protected($payment->getNote()));
        $outputSquare .= '</td></tr>' . "\n";
    }
    $refunds = $transaction->getRefunds();
    if (count($refunds)) {
        $outputSquare .= '<tr><td class="main">' . "\n";
        $outputSquare .= '<strong>Refunds: </strong>' . "\n";
        $outputSquare .= '</td><td class="main">&nbsp;</td></tr>' . "\n";
        foreach ($refunds as $refund) {
            $currency_code = $refund->getAmountMoney()->getCurrency();
            $amount = $currencies->format($refund->getAmountMoney()->getAmount() / (pow(10, $currencies->get_decimal_places($currency_code))), false, $currency_code) ;
            $outputSquare .= '<tr><td class="main">' . "\n";
            $outputSquare .= $refund->getCreatedAt() . "\n<br>" . $refund->getId() . "\n";
            $outputSquare .= '</td><td class="main">' . "\n";
            $outputSquare .= '-' . $amount . ' ' . $currency_code . ' ' . $refund->getStatus() . "\n";
            if ($refund->getReason()) $outputSquare .= '<br>' . nl2br(zen_output_string_protected($refund->getReason()));
            $outputSquare .= '</td></tr>' . "\n";
        }
    }

    $outputSquare .= '</table></td>' . "\n";
}


if (method_exists($this, '_doRefund')) {
    $outputRefund .= '<td><table class="noprint">' . "\n";
    $outputRefund .= '<tr style="background-color : #dddddd; border-style : dotted;">' . "\n";
    $outputRefund .= '<td class="main">' . MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TITLE . '<br />' . "\n";
    $outputRefund .= zen_draw_form('squarerefund', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doRefund', 'post', '', true) . zen_hide_session_id();;
    $outputRefund .= MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND . '<br />';
    $outputRefund .= MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_AMOUNT_TEXT . ' ' . zen_draw_input_field('refamt', '', 'length="10" placeholder="amount"') . '<br />';
    $outputRefund .= MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TENDER_ID . ' ' . zen_draw_input_field('tender_id', '', 'length="40" placeholder="tender ID"') . '<br />';
    $outputRefund .= MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TRANS_ID . ' ' . zen_draw_input_field('trans_id', '', 'length="40" placeholder="transaction ID"') . '<br />';
    $outputRefund .= MODULE_PAYMENT_SQUAREUP_TEXT_REFUND_CONFIRM_CHECK . zen_draw_checkbox_field('refconfirm', '', false) . '<br />';
    $outputRefund .= '<br />' . MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('refnote', 'soft', '50', '3', MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_DEFAULT_MESSAGE);
    $outputRefund .= '<br />' . MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_SUFFIX;
    $outputRefund .= '<br /><input type="submit" name="buttonrefund" value="' . MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_SQUAREUP_ENTRY_REFUND_BUTTON_TEXT . '" />';
    $outputRefund .= '</form>';
    $outputRefund .= '</td></tr></table></td>' . "\n";
}

if (method_exists($this, '_doCapt')) {
    $outputCapt .= '<td valign="top"><table class="noprint">' . "\n";
    $outputCapt .= '<tr style="background-color : #dddddd; border-style : dotted;">' . "\n";
    $outputCapt .= '<td class="main">' . MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_TITLE . '<br />' . "\n";
    $outputCapt .= zen_draw_form('squarecapture', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doCapture', 'post', '', true) . zen_hide_session_id();
    $outputCapt .= MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE . '<br />';
    $outputCapt .= MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_TRANS_ID . '<br />' . zen_draw_input_field('captauthid', '', 'length="40" placeholder="transaction ID"') . '<br />';
    $outputCapt .= MODULE_PAYMENT_SQUAREUP_TEXT_CAPTURE_CONFIRM_CHECK . zen_draw_checkbox_field('captconfirm', '', false) . '<br />';
    $outputCapt .= '<br />' . MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('captnote', 'soft', '50', '2', MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_DEFAULT_MESSAGE);
    $outputCapt .= '<br />' . MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_SUFFIX;
    $outputCapt .= '<br /><input type="submit" name="btndocapture" value="' . MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_SQUAREUP_ENTRY_CAPTURE_BUTTON_TEXT . '" />';
    $outputCapt .= '</form>';
    $outputCapt .= '</td></tr></table></td>' . "\n";
}

if (method_exists($this, '_doVoid')) {
    $outputVoid .= '<td valign="top"><table class="noprint">' . "\n";
    $outputVoid .= '<tr style="background-color : #dddddd; border-style : dotted;">' . "\n";
    $outputVoid .= '<td class="main">' . MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_TITLE . '<br />' . "\n";
    $outputVoid .= zen_draw_form('squarevoid', FILENAME_ORDERS, zen_get_all_get_params(['action']) . 'action=doVoid', 'post', '', true) . zen_hide_session_id();
    $outputVoid .= MODULE_PAYMENT_SQUAREUP_ENTRY_VOID . '<br />' . zen_draw_input_field('voidauthid', '', 'length="40" placeholder="transaction ID"');
    $outputVoid .= '<br />' . MODULE_PAYMENT_SQUAREUP_TEXT_VOID_CONFIRM_CHECK . zen_draw_checkbox_field('voidconfirm', '', false);
    $outputVoid .= '<br /><br />' . MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_TEXT_COMMENTS . '<br />' . zen_draw_textarea_field('voidnote', 'soft', '50', '3', MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_DEFAULT_MESSAGE);
    $outputVoid .= '<br />' . MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_SUFFIX;
    $outputVoid .= '<br /><input type="submit" name="ordervoid" value="' . MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_BUTTON_TEXT . '" title="' . MODULE_PAYMENT_SQUAREUP_ENTRY_VOID_BUTTON_TEXT . '" />';
    $outputVoid .= '</form>';
    $outputVoid .= '</td></tr></table></td>' . "\n";
}


// prepare output based on suitable content components
if (defined('MODULE_PAYMENT_SQUAREUP_STATUS') && MODULE_PAYMENT_SQUAREUP_STATUS != '') {
    $output = '<!-- BOF: square admin transaction processing tools -->';
    $output .= $outputStartBlock;
    $output .= $outputSquare;
    $output .= $outputEndBlock;
    $output .= '</tr><tr>' . "\n";
    $output .= $outputStartBlock;

    if (MODULE_PAYMENT_SQUAREUP_TRANSACTION_TYPE == 'authorize' || (isset($_GET['authcapt']) && $_GET['authcapt'] == 'on')) {
        if (method_exists($this, '_doRefund')) $output .= $outputRefund;
        if (method_exists($this, '_doCapt')) $output .= $outputCapt;
        if (method_exists($this, '_doVoid')) $output .= $outputVoid;
    } else {
        if (method_exists($this, '_doRefund')) $output .= $outputRefund;
    }
    $output .= $outputEndBlock;
    $output .= '<!-- EOF: square admin transaction processing tools -->';
}
