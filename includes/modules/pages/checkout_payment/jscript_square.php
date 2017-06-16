<?php
/**
 * Javascript to prep functionality for Square payment module
 *
 * @package square
 * @copyright Copyright 2003-2017 Zen Cart Development Team
 * @license http://www.zen-cart.com/license/2_0.txt GNU Public License V2.0
 * @version $Id: Author: Chris Brown <drbyte@zen-cart.com> New in v1.5.6 $
 */
if (!defined(MODULE_PAYMENT_SQUARE_STATUS) || MODULE_PAYMENT_SQUARE_STATUS != 'True' || (!defined('MODULE_PAYMENT_SQUARE_APPLICATION_ID') || MODULE_PAYMENT_SQUARE_ACCESS_TOKEN == '')) {
    return false;
}
?>
<script type="text/javascript" src="https://js.squareup.com/v2/paymentform"></script>


<script type="text/javascript">
    var cardNonce;
    var paymentForm = new SqPaymentForm({
        applicationId: '<?php echo MODULE_PAYMENT_SQUARE_APPLICATION_ID; ?>',
        inputClass: 'paymentInput',
        inputStyles: [
            {
                fontSize: '14px',
                padding: '7px 12px',
                backgroundColor: "white"
            }
        ],
        cardNumber: {
            elementId: 'square_cc-number',
            placeholder: '•••• •••• •••• ••••'
        },
        cvv: {
            elementId: 'square_cc-cvv',
            placeholder: 'CVV'
        },
        expirationDate: {
            elementId: 'square_cc-expires',
            placeholder: 'MM/YY'
        },
        postalCode: {
            elementId: 'square_cc-postcode',
            placeholder: '11111'
        },
        callbacks: {
            cardNonceResponseReceived: function (errors, nonce, cardData) {
                if (errors) {
                    console.error("Encountered errors:");
                    var error_html = ""
                    errors.forEach(function (error) {
                        console.error('  ' + error.message);
                        error_html += "<li> " + error.message + " </li>";
                    });
                    document.getElementById('card-errors').innerHTML = '<ul>' + error_html + '</ul>';
                    $('#paymentSubmitButton').disabled = false;
                } else {
                    // success
                    $('#paymentSubmitButton').disabled = true;
                    $("#card-errors").empty()
                    document.getElementById('card-nonce').value = nonce;
                    document.getElementById('card-type').value = cardData.card_brand;
                    document.getElementById('card-four').value = cardData.last_4;
                    document.getElementById('card-exp').value = cardData.exp_month.toString() + cardData.exp_year.toString().substr(-2);
                    document.getElementsByName('checkout_payment')[0].submit();
                }

            },
            unsupportedBrowserDetected: function () {
                document.getElementById('card-errors').innerHTML = '<p class="error alert">This browser is not supported for Square Payments. Please contact us to let us know!  Meanwhile, please pay using an alternate method; or shop using a different browser such as FireFox or Chrome.</p>';
                paymentForm.destroy();
            },

            inputEventReceived: function (inputEvent) {
                switch (inputEvent.eventType) {
                    case 'focusClassAdded':
                        methodSelect('pmt-square');
                        break;
                    case 'cardBrandChanged':
                        document.getElementById('sq-card-brand').innerHTML = inputEvent.cardBrand;
                        break;
                }
            },
            paymentFormLoaded: function () {
                paymentForm.setPostalCode('<?php echo $order->billing['postcode']; ?>');
            }
        }
    });

    $(function () {
        $.ajaxSetup({
            headers: {"X-CSRFToken": "<?php echo $_SESSION['securityToken']; ?>"}
        });
        $('#paymentSubmit .submit_button').click(function (e) {
            e.preventDefault();
            paymentForm.requestCardNonce();
        });
    });
</script>
