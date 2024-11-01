// console.clear();
var  appId = give_square_keys.appid;
var  locationId = give_square_keys.location_id;
var  id = jQuery('input[name=give-form-id]').val();

// console.log(appId);
// console.log(locationId);
    async function initializeCard(payments,id) {

      const card = await payments.card();
      await card.attach('#card-container');
	  
	  async function handlePaymentMethodSubmission(event, paymentMethod,id) {
		  // console.log(paymentMethod);
		  //debugger;
		event.preventDefault();

		try {
		  // disable the submit button as we await tokenization and make a
		  // payment request.
		  cardButton.disabled = true;
		  
			// console.log('tokenize request');
		  const token =  tokenize(paymentMethod,cardButton,id);
			// console.log('token Success'+ token);  
		  
		  //displayPaymentResults('SUCCESS');

		  console.debug('Payment Success', paymentResults);
		} catch (e) {
		  cardButton.disabled = false;
		  //displayPaymentResults('FAILURE');
		  console.error(e.message);
		}
	  }
		console.clear();
	  const cardButton = document.getElementsByClassName(
		'give-submit'
	  )[0];
	  cardButton.addEventListener('click', async function (event) {
		await handlePaymentMethodSubmission(event, card,id);
	  });
	  
      return card;

    }
    // Call this function to send a payment token, buyer name, and other details
    // to the project server code so that a payment can be created with 
    // Payments API
  

    // This function tokenizes a payment method. 
    // The ‘error’ thrown from this async function denotes a failed tokenization,
    // which is due to buyer error (such as an expired card). It is up to the
    // developer to handle the error and provide the buyer the chance to fix
    // their mistakes.
    async function tokenize(paymentMethod,cardButton,id) {
      const tokenResult =  await paymentMethod.tokenize();
	  
	  // console.log(tokenResult);
      if (tokenResult.status === 'OK') {
		// console.log('NONCE: ' + tokenResult.token);
		document.getElementById('card-nonce').value = tokenResult.token;
		jQuery('form.give-form-'+id).submit();
      } else {
		cardButton.disabled = false;
        let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
        if (tokenResult.errors) {
          errorMessage += ` and errors: ${JSON.stringify(
            tokenResult.errors 
          )}`;
        }
        throw new Error(errorMessage);
      }
    }

    // Helper method for displaying the Payment Status on the screen.
    // status is either SUCCESS or FAILURE;
    function displayPaymentResults(status) {
      const statusContainer = document.getElementById(
        'payment-status-container'
      );
      if (status === 'SUCCESS') {
        statusContainer.classList.remove('is-failure');
        statusContainer.classList.add('is-success');
      } else {
        statusContainer.classList.remove('is-success');
        statusContainer.classList.add('is-failure');
      }

      statusContainer.style.visibility = 'visible';
    }
	
    document.addEventListener('DOMContentLoaded', async function () {
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}
		const payments = window.Square.payments(appId, locationId);
		let card;
		try {
			card = initializeCard(payments,id);

		} catch (e) {
			console.error('Initializing Card failed', e);
		return;
		}
		jQuery(document).ajaxSuccess(function(event,jqXHR,options) {
			// console.log(options.data.split('&'));
			if(options.data.split('&')[0] == 'action=give_load_gateway' && options.data.split('&')[4] == 'give_payment_mode=square'){
				//console.log('PAYMENTS:' + JSON.stringify(payments));
				try {
					card = initializeCard(payments,id);
				} catch (e) {
					console.error('Initializing Card failed', e);
				return;
				}
			}
		});
		/* 
		jQuery(".give-btn").click(function(){
			console.log(jQuery('#card-container').is(':visible'));
			console.log(jQuery('#card-container').html().length);
			if(jQuery('#card-container').is(':visible')){
				try {
					card = initializeCard(payments,id);
				} catch (e) {
					console.error('Initializing Card failed', e);
				return;
				}
			}
		});
       */
		console.clear();
    });
	jQuery(window).load(function() {
		console.clear();
	});