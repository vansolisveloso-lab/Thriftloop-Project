import { useEffect, useRef, useState } from "react";

import { useCart } from "./CartContext";


// Picks the customer up when PayMongo redirects back to the app:
//   /?payment=success&order=TL-...&email=...
//   /?payment=cancelled&order=TL-...&email=...
//
// Confirms or auto-cancels the order server-side, clears the bag only after
// a verified payment, then lands on the Orders page with a short notice.
function PaymentReturnHandler({ user, navigate }) {

  const { clearCart } = useCart();

  const [notice, setNotice] = useState(null);

  const handledRef = useRef(false);


  useEffect(() => {

    // Run once, even under StrictMode's double-invoked effects.
    if (handledRef.current) return;


    const params = new URLSearchParams(window.location.search);

    const payment = params.get("payment");

    const orderNumber = params.get("order");

    const email = params.get("email");

    if (!payment || !orderNumber) return;

    handledRef.current = true;


    // Strip the parameters so a refresh cannot re-run the return flow.
    window.history.replaceState({}, "", window.location.pathname);


    const finish = (kind, message) => {

      setNotice({ kind, message });

      navigate("orders");

      window.setTimeout(() => setNotice(null), 8000);
    };


    (async () => {
      try {
        const response = await fetch(
          "http://localhost/thriftloop/api/payments/return.php",
          {
            method: "POST",

            headers: {
              "Content-Type": "application/json"
            },

            body: JSON.stringify({
              order_number: orderNumber,

              email: email || (user && user.email) || "",

              action: payment === "cancelled" ? "cancelled" : "success",
            }),
          }
        );

        const data = await response.json();


        if (data.payment_status === "paid") {

          clearCart();

          finish(
            "success",
            "PAYMENT CONFIRMED — ORDER " + orderNumber + " IS PAID. THANK YOU!"
          );

        } else if (data.order_status === "cancelled") {

          finish(
            "cancelled",
            data.message ||
            "PAYMENT CANCELLED — ORDER " + orderNumber + " WAS CANCELLED."
          );

        } else if (!data.success) {

          finish("error", data.message || "COULD NOT VERIFY YOUR PAYMENT.");

        } else {

          finish(
            "info",
            data.message ||
            "PAYMENT NOT CONFIRMED YET — ORDER " + orderNumber + " IS STILL PENDING."
          );
        }

      } catch {

        finish(
          "error",
          "CANNOT REACH THE THRIFTLOOP SERVER TO VERIFY YOUR PAYMENT."
        );
      }
    })();

  }, [user, navigate, clearCart]);


  if (!notice) return null;


  return (
    <div
      className={
        notice.kind === "error"
          ? "login-error payment-notice"
          : "auth-success payment-notice"
      }
    >
      {notice.message}
    </div>
  );

}

export default PaymentReturnHandler;
