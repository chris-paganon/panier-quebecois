<?php
if ( !defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

//Remove default emails for entreprise subscriptions

add_filter( 'woocommerce_email_recipient_customer_completed_order', 'product_cat_avoid_email_notification', 10, 2 );
add_filter( 'woocommerce_email_recipient_customer_completed_renewal_order', 'product_cat_avoid_email_notification', 10, 2 );

function product_cat_avoid_email_notification( $recipient, $order ) {
  if ( myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) {

    return ''; // If it's found, we return an empty recipient
  }

  return $recipient;
}

//Change email subject for processing order given preferred language
add_filter( 'woocommerce_email_subject_customer_processing_order', 'bbloomer_change_processing_email_subject', 10, 2 );

function bbloomer_change_processing_email_subject( $subject, $order ) {
  $custom_language = get_post_meta( $order->get_id(), '_billing_language', true );

  //Input text below for customers
  if ( $custom_language == 'francais' ) {
    if ( myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) {
      $subject = 'Félicitations! Vous êtes maintenant abonné(e) au Panier Québécois'; //First subscription
    } else {
      $subject = 'Commande confirmée et détails de livraison'; //Normal order
    }
  } else {
    if ( myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) {
      $subject = 'Congratulations! You have subscribed to Panier Québécois'; //First subscription
    } else {
      $subject = 'Order confirmed and delivery details'; //Normal order
    }
  }
  return $subject;
}

//Change email subject for all completed order given preferred language
add_filter( 'woocommerce_email_subject_customer_completed_order', 'bbloomer_change_completed_email_subject', 10, 2 );
add_filter( 'woocommerce_subscriptions_email_subject_customer_completed_renewal_order', 'bbloomer_change_completed_email_subject', 10, 2 );

function bbloomer_change_completed_email_subject( $subject, $order ) {
  $custom_language = get_post_meta( $order->get_id(), '_billing_language', true );

  //Input text below
  foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
    $shipping_rate_id = $shipping_item->get_method_id();
    $method_array = explode( ':', $shipping_rate_id );
    $shipping_method_id = reset( $method_array );

    if ( $custom_language == 'francais' ) {

      // For local pickup shipping method
      if ( 'local_pickup' == $shipping_method_id ) {
        $subject = 'Bonne nouvelle, votre panier a été livré en point de collecte!';
        //For home delivery
      } else {
        $subject = 'Bonne nouvelle, votre panier a été livré!';
      }
    } else {

      // For local pickup shipping method
      if ( 'local_pickup' == $shipping_method_id ) {
        $subject = 'Good news, your basket was delivered in its pickup location!';
        //For home delivery
      } else {
        $subject = 'Good news, your basket was delivered';
      }
    }
  }
  return $subject;
}

//Change email header given preferred language for all processing and completed orders
add_filter( 'woocommerce_email_heading_customer_processing_order', 'change_email_title_header_depending_of_product_id', 10, 2 );
add_filter( 'woocommerce_email_heading_customer_completed_order', 'change_email_title_header_depending_of_product_id', 10, 2 );
add_filter( 'woocommerce_email_heading_customer_renewal_order', 'change_email_title_header_depending_of_product_id', 10, 2 );

function change_email_title_header_depending_of_product_id( $email_heading, $order ) {
  //Get customer preferred language from billing form
  $custom_language = get_post_meta( $order->get_id(), '_billing_language', true );

  //French version of emails
  if ( $custom_language == 'francais' ) {
    $email_heading = 'Merci pour votre commande';
  } else {
    $email_heading = 'Thank you for your order';
  }
  return $email_heading;
}

// -------------- MAIN EMAIL TEXT (ABOVE TABLE) -------------- //

add_action( 'woocommerce_email_before_order_table', 'myfct_order_email_custom_text', 5, 4 );

function myfct_order_email_custom_text( $order, $sent_to_admin, $plain_text, $email ) {
  $fmt_fr = new IntlDateFormatter( 'fr_FR', IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, NULL, IntlDateFormatter::GREGORIAN, 'EEEE dd MMMM y' );
  $fmt_en = new IntlDateFormatter( NULL, IntlDateFormatter::MEDIUM, IntlDateFormatter::NONE, NULL, IntlDateFormatter::GREGORIAN, 'EEEE MMMM dd, y' );

  // ---- VARIABLES ---- //
  $order_id = $order->get_id();
  $custom_language = get_post_meta( $order_id, '_billing_language', true );
  if ( empty( $custom_language ) ) {
    $custom_language = 'francais';
  }
  $delivery_timestamp = get_post_meta( $order_id, '_orddd_timestamp', true );
  if ( empty( $delivery_timestamp ) ) {
    $delivery_timestamp = 0;
  }
  $pickup_time = get_post_meta( $order_id, '_orddd_time_slot', true );
  if ( empty( $pickup_time ) ) {
    $pickup_time = '';
  }
  $pickup_location = get_post_meta( $order_id, 'Point de collecte', true );
  if ( empty( $pickup_location ) ) {
    $pickup_location = '';
  }

  $delivery_time_slot_fr = get_post_meta( $order_id, 'Horaires de livraison', true );
  $delivery_time_slot_en = $delivery_time_slot_fr;

  $nps_page_url = get_permalink( 17950 );

  // ---- EMAIL CONTENT ---- //
  //CONTACT US
  $contact_us_fr = '<p>Si vous avez la moindre question relative à votre commande, n\'hésitez pas à nous contacter à <strong>commandes@panierquebecois.ca ou directement par téléphone au (514) 647-8843,</strong> nous nous ferons un plaisir de vous aider.</p>';

  $contact_us_en = '<p>If you have any question about your order, don\'t hesitate to contact us at <strong>commandes@panierquebecois.ca or by calling us here: (514) 647-8843,</strong> it will be our pleasure to help you.</p>';

  //DELIVERY
  if ( $delivery_timestamp !== 0 ) {
    $processing_delivery_fr = '<p>
        Votre commande sera déposée devant votre porte le <strong>' . $fmt_fr->format( $delivery_timestamp ) . '</strong> entre ' . $delivery_time_slot_fr . '. Notre livreur vous appellera et/ou sonnera à votre porte dès la livraison effectuée.
        <br/><br/>
        Si vous avez commandé des produits frais avec Panier Québécois la semaine passée, <strong>merci de préparer vos blocs réfrigérants et votre sac isotherme afin que notre livreur puisse les récupérer lors de son passage chez vous.</strong></p>';
  } else {
    $processing_delivery_fr = '<p>Votre commande a bien été reçus. Nous vous informerons par courriel des détails de votre livraison dans les semaines à venir.</p>';
  }

  if ( $delivery_timestamp !== 0 ) {
    $processing_delivery_en = '<p>
			We will drop your order in front of your door on <strong>' . $fmt_en->format( $delivery_timestamp ) . '</strong> between ' . $delivery_time_slot_en . '. Our driver will call and/or ring your doorbell when they arrive.
			<br/><br/>
			If you have ordered cold products from Panier Québécois last week, <strong>please prepare your ice packs and isotherm bag so that our driver can pick it up during his delivery.</strong></p>';
  } else {
    $processing_delivery_en = '<p>We have received your order. We will inform you by email of the delivery details in the coming weeks.</p>';
  }

  $completed_delivery_fr_intro = '<p>Bonne nouvelle, votre Panier Québécois a été livré! Si vous ne l\'avez pas déjà récupéré, notre livreur l\'a déposé devant votre porte.</p>';

  $completed_delivery_fr_main = '<p>Si vous avez commandé des produits frais, <strong>merci de conserver vos blocs réfrigérants et votre sac isotherme.</strong> Ils seront récupérés par notre livreur lors de votre prochaine commande, et nettoyés par nos soins.</p>
		<p>Que pensez-vous de Panier Québécois? <a href="' . $nps_page_url . '">Donnez-nous votre avis ici</a>!</p>';

  $completed_delivery_en_intro = '<p>Good news, your basket was delivered! If you have not retrieved it yet, our driver has dropped it in front of your door.</p>';

  $completed_delivery_en_main = '<p>If you have ordered cold products, <strong>please keep your ice pack and isotherm bag.</strong> They will be retrieved by our driver during your next order and cleaned by our team.</p>
		<p>What do you think of Panier Québécois? <a href="' . $nps_page_url . '">Let us know here</a>!</p>';

  //PICKUP
  $processing_pickup_fr = '<p>
			Vous avez choisi la livraison en point de collecte. Votre commande sera prête pour ramassage le <strong>' . $fmt_fr->format( $delivery_timestamp ) . ' entre ' . $pickup_time . ' au point suivant:</strong>
			<br/>
			' . $pickup_location . '
			<br/> <br/>
			Instructions pour la collecte:
			<br/> <br/>
			<strong>Nous vous recommandons de vous munir de sacs réutilisables afin de pouvoir récupérer votre commande.</strong> Les boites doivent être laissées sur place, ainsi que les sacs isothermes réutilisables et les blocs réfrigérants.</p>';

  $processing_pickup_en = '<p>
			You have chosen to pickup your order. Your order will be ready for pickup on <strong>' . $fmt_en->format( $delivery_timestamp ) . ' between ' . $pickup_time . ' at the following location:</strong>
			<br/>
			' . $pickup_location . '
			<br/> <br/>
			Instructions for pickup:
			<br/> <br/>
			<strong>We recommend that you come with reusable bags in order to retrieve your products.</strong> The boxes must be left at the location, as well as the reusable isotherm bags and ice packs.</p>';

  $completed_pickup_fr_intro = '<p>Votre panier a été livré au point de collecte suivant: 
			<br/>
			' . $pickup_location . '
			<br/> <br/>
			<strong>Nous vous recommandons de vous munir de sacs réutilisables afin de pouvoir récupérer votre commande.</strong> Les boites doivent être laissées sur place, ainsi que les sacs isothermes réutilisables et les blocs réfrigérants.
			<br/> <br/>
			Si vous ne pouvez pas vous rendre au point de collecte aujourd\'hui, veuillez nous en informer le plus rapidement possible à commandes@panierquebecois.ca ou directement par téléphone au (514) 647-8843.</p>
			<p>Que pensez-vous de Panier Québécois? <a href="' . $nps_page_url . '">Donnez-nous votre avis ici</a>!</p>';

  $completed_pickup_en_intro = '<p>Your basket was just delivered at the following pickup point: 
			<br/>
			' . $pickup_location . '
			<br/> <br/>
			<strong>We recommend that you come with reusable bags in order to retrieve your products.</strong> The boxes must be left at the location, as well as the reusable isotherm bags and ice packs.
			<br/> <br/> If you are unable to pickup your basket today, please inform us as soon as possible at commandes@panierquebecois.ca or by calling us here: (514) 647-8843.</p>
			<p>What do you think of Panier Québécois? <a href="' . $nps_page_url . '">Let us know here</a>!</p>';

  //ENTREPRISE
  $processing_sub_entr_first_fr = '<p>Votre demande d’abonnement à notre service hebdomadaire de panier de fruits a bien été reçue. 
			Votre livraison aura lieu tous les lundi entre 13h et 16h. Le panier sera livré à l’adresse indiquée lors de votre commande.
			<br/> <br/>
			Votre abonnement est valide pour une période de 1 mois, et sera renouvelé automatiquement sauf avis contraire de votre part. 
			Vous pouvez suspendre ou annuler votre abonnement pour la période suivante, sans frais, directement sur <a href="https://panierquebecois.ca/mon-compte/subscriptions/">votre espace client</a>.
			<br/> <br/>
			Pour toutes questions, contactez notre département de service à la clientèle à info@panierquebecois.ca
			<br/> <br/>
			Vous trouverez ci-dessous les détails concernant votre commande.</p>';

  $processing_sub_entr_first_en = '<p>We have received your subscription to our weekly fruit basket service.
			The deliveries will happen every Monday between 1pm and 4pm. The basket will be delivered at the address you entered while making the order.
			<br/> <br/>

			Your subscription is valid for a duration of 1 month and will be renewed automatically unless you ask us to cancel it.
			You can suspend or cancel your subscription for the next period, at no extra cost, directly on <a href="https://panierquebecois.ca/en/mon-compte/subscriptions/">your account</a>.
			<br/> <br/>
			
			If you have any questions, contact our customer service department at info@panierquebecois.ca
			<br/> <br/>

			Find below the details of your order.</p>';

  $processing_sub_entr_renewal_fr = '<p>Le renouvellement de votre abonnement à notre service hebdomadaire de panier de fruits est confirmé pour le prochain mois. 
			Votre livraison aura lieu tous les lundi entre 13h et 16h. Le panier sera livré à l’adresse indiquée lors de votre commande. 
			<br/> <br/>
			Votre abonnement est valide pour une période de 1 mois, et sera renouvelé automatiquement sauf avis contraire de votre part. Vous pouvez suspendre ou annuler votre abonnement pour la période suivante, sans frais, directement sur <a href="https://panierquebecois.ca/mon-compte/subscriptions/">votre compte client</a>.
			<br/> <br/>
			Pour toutes questions, contactez notre département de service à la clientèle à info@panierquebecois.ca
			<br/> <br/>
			Vous trouverez ci-dessous les détails concernant votre commande.</p>';

  $processing_sub_entr_renewal_en = '<p>Your subscription renewal to our weekly fruit basket service has been confirmed for the following month.
			The deliveries will happen every Monday between 1pm and 4pm. The basket will be delivered at the address you entered while making the order.
			<br/> <br/>

			Your subscription is valid for a duration of 1 month and will be renewed automatically unless you ask us to cancel it.
			You can suspend or cancel your subscription for the next period, at no extra cost, directly on <a href="https://panierquebecois.ca/en/mon-compte/subscriptions/">your account</a>.
			<br/> <br/>
			
			If you have any questions, contact our customer service department at info@panierquebecois.ca
			<br/> <br/>

			Find below the details of your order.</p>';

  //GIFT CARDS
  $gift_card_fr = '<p>Merci pour votre achat de carte(s) cadeau(x). 
			<br/> <br/>
			Un email avec le code à utiliser sur le site a été envoyé (ou sera envoyé à la date que vous avez sélectionné) à l\'adresse email du bénéficiaire. Le code peut être utilisé sur l’ensemble des produits de notre site en une, deux, trois ou plusieurs fois.</p>';

  $gift_card_en = '<p>Thank you for your gift card(s) order. 
			<br/> <br/>
			An email with the code to use on the website was sent (or will be sent on the date you selected) to the email address of the beneficiary. The code can be used on all the products of our website in one, two, three or more orders</p>';

  // ---- CONITIONS TO DISPLAY STRINGS---- //

  // ------- FRENCH ------- //
  if ( $custom_language == 'francais' ) {
    //Additional text if a gift card is ordered
    if ( ( 'customer_processing_order' == $email->id || 'customer_completed_order' == $email->id ) ) {

      if ( myfct_return_true_if_has_category_from_order( $order, 'carte-cadeau' ) ) {
        echo $gift_card_fr;
      }
    }

    // ------- PROCESSING ------- //

    //For all first processing subscription order
    if ( ( 'customer_processing_order' == $email->id ) ) {
      //For entreprise
      if ( myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) {
        echo $processing_sub_entr_first_fr;
      }
    }

    //For entreprise subscription renewal
    if ( ( 'customer_processing_renewal_order' == $email->id && myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) ) {
      echo $processing_sub_entr_renewal_fr;
    }

    //For all processing email notifications except entreprise
    if ( ( ( 'customer_processing_order' == $email->id ) && !myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) ) {
      //For simple processing orders
      foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
        $shipping_rate_id = $shipping_item->get_method_id();
        $method_array = explode( ':', $shipping_rate_id );
        $shipping_method_id = reset( $method_array );

        // For local pickup shipping method
        if ( 'local_pickup' == $shipping_method_id ) {

          echo $processing_pickup_fr;
          break;
        }

        // For other shipping methods
        else {
          echo $processing_delivery_fr;
        }
      }

      //add contact us
      echo $contact_us_fr;
    }

    // ------- COMPLETED ------- //

    // For completed email notifications
    if ( 'customer_completed_order' == $email->id ) {
      //For simple completed orders (INTRO & MAIN)
      foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
        $shipping_rate_id = $shipping_item->get_method_id();
        $method_array = explode( ':', $shipping_rate_id );
        $shipping_method_id = reset( $method_array );

        // For local pickup shipping method
        if ( 'local_pickup' == $shipping_method_id ) {
          echo $completed_pickup_fr_intro;
          break;
          // For other shipping methods
        } else {
          echo $completed_delivery_fr_intro . $completed_delivery_fr_main;
        }
      }
    }
  }

  // ------- ENGLISH ------- //
  else {

    //Additional text if a gift card is ordered
    if ( ( 'customer_processing_order' == $email->id || 'customer_completed_order' == $email->id ) ) {
      if ( myfct_return_true_if_has_category_from_order( $order, 'carte-cadeau' ) ) {
        echo $gift_card_en;
      }
    }

    // ------- PROCESSING ------- //
    //For first processing subscription order
    if ( ( 'customer_processing_order' == $email->id ) ) {
      //For entreprise
      if ( myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) {
        echo $processing_sub_entr_first_en;
      }
    }

    //For entreprise subscription renewal
    if ( ( 'customer_processing_renewal_order' == $email->id && myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) ) {
      echo $processing_sub_entr_renewal_en;
    }

    //For all processing email notifications except entreprise
    if ( ( ( 'customer_processing_order' == $email->id ) && !myfct_return_true_if_has_category_from_order( $order, 'entreprise' ) ) ) {
      //Text for physical processing orders
      foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {
        $shipping_rate_id = $shipping_item->get_method_id();
        $method_array = explode( ':', $shipping_rate_id );
        $shipping_method_id = reset( $method_array );

        // For local pickup shipping method
        if ( 'local_pickup' == $shipping_method_id ) {

          echo $processing_pickup_en;
          break;
        }

        // For other shipping methods
        else {
          echo $processing_delivery_en;
        }
      }

      //add contact us
      echo $contact_us_en;
    }

    // ------- COMPLETED ------- //

    // For completed email notifications
    if ( 'customer_completed_order' == $email->id ) {
      //For simple completed orders (INTRO & MAIN)
      foreach ( $order->get_items( 'shipping' ) as $shipping_item ) {

        $shipping_rate_id = $shipping_item->get_method_id();
        $method_array = explode( ':', $shipping_rate_id );
        $shipping_method_id = reset( $method_array );

        // For local pickup shipping method only
        if ( 'local_pickup' == $shipping_method_id ) {

          echo $completed_pickup_en_intro;
          break;
        }

        // For other shipping methods
        else {
          echo $completed_delivery_en_intro . $completed_delivery_en_main;
        }
      }
    }
  }
}