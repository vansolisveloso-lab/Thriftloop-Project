import { useState } from "react";

import { useCart } from "../components/CartContext";

function Checkout({ user }) {
  const {
    cartItems,
    subtotal,
    clearCart,
  } = useCart();

  const shipping = 100;
  const total = subtotal + shipping;

  const [firstName, setFirstName] = useState(
    user?.first_name || ""
  );

  const [lastName, setLastName] = useState(
    user?.last_name || ""
  );

  const [email, setEmail] = useState(
    user?.email || ""
  );

  const [phone, setPhone] = useState("");

  const [address, setAddress] = useState("");

  const [city, setCity] = useState("");

  const [postalCode, setPostalCode] = useState("");

  const [province, setProvince] = useState("");

  const [error, setError] = useState("");

  const [placing, setPlacing] = useState(false);

  const [paymentMethod, setPaymentMethod] = useState("online");

  const [placed, setPlaced] = useState("");


  const handlePlaceOrder = async () => {
    setError("");

    if (!user) {
      setError("PLEASE SIGN IN TO PLACE YOUR ORDER.");
      return;
    }

    if (cartItems.length === 0) {
      setError("YOUR CART IS EMPTY.");
      return;
    }

    setPlacing(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/orders/create.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            first_name: firstName,
            last_name: lastName,
            email: email || user.email,
            phone: phone,
            address: address,
            city: city,
            postal_code: postalCode,
            province: province,

            payment_method: paymentMethod,

            items: cartItems.map((item) => ({
              product_id: parseInt(item.id, 10),
              quantity: item.quantity,
            })),
          }),
        }
      );

      const data = await response.json();

      if (!data.success) {
        setError(
          data.message || "FAILED TO PLACE ORDER."
        );

        return;
      }


      // Cash on delivery — the order is placed right here, no redirect.
      if (paymentMethod === "cod") {
        clearCart();

        setPlaced("ORDER PLACED — PAY CASH WHEN YOUR ORDER ARRIVES.");

        return;
      }


      // The order exists — open the PayMongo hosted checkout next.
      let sessionData = null;

      try {
        const sessionResponse = await fetch(
          "http://localhost/thriftloop/api/payments/create_session.php",
          {
            method: "POST",

            headers: {
              "Content-Type": "application/json"
            },

            body: JSON.stringify({
              order_number: data.order.order_number,

              email: email || user.email,
            }),
          }
        );

        sessionData = await sessionResponse.json();

      } catch {
        sessionData = null;
      }


      if (!sessionData || !sessionData.success) {

        // Roll the order back so stock returns and the retry is clean.
        try {
          await fetch(
            "http://localhost/thriftloop/api/orders/cancel.php",
            {
              method: "POST",

              headers: {
                "Content-Type": "application/json"
              },

              body: JSON.stringify({
                order_number: data.order.order_number,

                email: email || user.email,
              }),
            }
          );

        } catch {
          // Best effort — the order can still be cancelled from Orders.
        }

        setError(
          (sessionData && sessionData.message) ||
          "COULD NOT START THE PAYMONGO CHECKOUT."
        );

        return;
      }


      // The bag is only emptied once PayMongo has taken over the order.
      // App.jsx confirms the payment when the customer returns.
      clearCart();

      window.location.href = sessionData.checkout_url;

    } catch {
      setError(
        "CANNOT REACH THE THRIFTLOOP SERVER."
      );

    } finally {
      setPlacing(false);
    }
  };

  return (
    <main className="checkout-page">

      <section className="checkout-header">

        <p className="section-label">
          [ THRIFTLOOP CHECKOUT ]
        </p>

        <h1>
          CHECK
          <br />
          OUT.
        </h1>

      </section>


      <section className="checkout-content">

        <div className="checkout-form">

          {/* CUSTOMER INFORMATION */}

          <div className="form-section">

            <p className="section-label">
              [ 01 / CUSTOMER INFORMATION ]
            </p>

            <div className="form-grid">

              <div className="form-group">
                <label>FIRST NAME</label>

                <input
                  type="text"
                  placeholder="Your first name"
                  value={firstName}
                  onChange={(event) =>
                    setFirstName(event.target.value)
                  }
                />
              </div>

              <div className="form-group">
                <label>LAST NAME</label>

                <input
                  type="text"
                  placeholder="Your last name"
                  value={lastName}
                  onChange={(event) =>
                    setLastName(event.target.value)
                  }
                />
              </div>

            </div>


            <div className="form-group">
              <label>EMAIL ADDRESS</label>

              <input
                type="email"
                placeholder="your@email.com"
                value={email}
                onChange={(event) =>
                  setEmail(event.target.value)
                }
              />
            </div>


            <div className="form-group">
              <label>PHONE NUMBER</label>

              <input
                type="tel"
                placeholder="09XXXXXXXXX"
                value={phone}
                onChange={(event) =>
                  setPhone(event.target.value)
                }
              />
            </div>

          </div>


          {/* DELIVERY ADDRESS */}

          <div className="form-section">

            <p className="section-label">
              [ 02 / DELIVERY ADDRESS ]
            </p>


            <div className="form-group">
              <label>ADDRESS</label>

              <input
                type="text"
                placeholder="House number, street"
                value={address}
                onChange={(event) =>
                  setAddress(event.target.value)
                }
              />
            </div>


            <div className="form-grid">

              <div className="form-group">
                <label>CITY</label>

                <input
                  type="text"
                  placeholder="City"
                  value={city}
                  onChange={(event) =>
                    setCity(event.target.value)
                  }
                />
              </div>


              <div className="form-group">
                <label>POSTAL CODE</label>

                <input
                  type="text"
                  placeholder="Postal code"
                  value={postalCode}
                  onChange={(event) =>
                    setPostalCode(event.target.value)
                  }
                />
              </div>

            </div>


            <div className="form-group">

              <label>PROVINCE</label>

              <select
                value={province}
                onChange={(event) =>
                  setProvince(event.target.value)
                }
              >
                <option value="">
                  Select province
                </option>
                <option>Cavite</option>
                <option>Laguna</option>
                <option>Batangas</option>
                <option>Rizal</option>
                <option>Metro Manila</option>
              </select>

            </div>

          </div>


          {/* PAYMENT METHOD */}

          <div className="form-section">

            <p className="section-label">
              [ 03 / PAYMENT METHOD ]
            </p>


            <div className="payment-options">

              <div className="payment-option">

                <input
                  type="radio"
                  name="payment"
                  checked={paymentMethod === "online"}
                  onChange={() => setPaymentMethod("online")}
                />


                <div>

                  <strong>
                    PAYMONGO
                  </strong>

                  <span>
                    Secure online payment powered by PayMongo.
                  </span>

                </div>

              </div>


              <div className="payment-option">

                <input
                  type="radio"
                  name="payment"
                  checked={paymentMethod === "cod"}
                  onChange={() => setPaymentMethod("cod")}
                />


                <div>

                  <strong>
                    CASH ON DELIVERY
                  </strong>

                  <span>
                    Pay in cash when your order arrives at your door.
                  </span>

                </div>

              </div>

            </div>

          </div>

        </div>


        {/* ORDER SUMMARY */}

        <aside className="checkout-summary">

          <p className="section-label">
            [ ORDER SUMMARY ]
          </p>


          {cartItems.length === 0 && (

            <p className="checkout-note">
              YOUR BAG IS EMPTY.
            </p>

          )}


          {cartItems.map((item) => (

            <div
              className="checkout-item"
              key={item.id}
            >

              <div>

                <strong>
                  {item.name}
                </strong>

                <span>
                  QTY: {item.quantity}
                </span>

              </div>

              <span>
                ₱{(item.price * item.quantity).toLocaleString()}
              </span>

            </div>

          ))}


          <div className="summary-row">

            <span>
              SUBTOTAL
            </span>

            <span>
              ₱{subtotal.toLocaleString()}
            </span>

          </div>


          <div className="summary-row">

            <span>
              SHIPPING
            </span>

            <span>
              ₱100
            </span>

          </div>


          <div className="summary-total">

            <span>
              TOTAL
            </span>

            <strong>
              ₱{total.toLocaleString()}
            </strong>

          </div>


          {error && (
            <div className="login-error">
              {error}
            </div>
          )}


          {placed ? (

            <div className="auth-success">
              {placed}
            </div>

          ) : (

            <>

              <button
                className="place-order-button"
                onClick={handlePlaceOrder}
                disabled={placing}
              >
                {placing
                  ? "PLACING YOUR ORDER..."
                  : paymentMethod === "cod"
                    ? "PLACE ORDER (COD) →"
                    : "PAY WITH PAYMONGO →"}
              </button>


              <p className="checkout-note">
                {paymentMethod === "cod"
                  ? "Pay cash to the rider when your order arrives."
                  : "Your payment will be securely processed through PayMongo."}
              </p>

            </>

          )}

        </aside>

      </section>

    </main>
  );
}

export default Checkout;
