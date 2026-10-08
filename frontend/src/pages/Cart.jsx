import { useCart } from "../components/CartContext";

function Cart({ navigate }) {
  const {
    cartItems,
    increaseQuantity,
    decreaseQuantity,
    removeFromCart,
    subtotal,
  } = useCart();

  const shipping = cartItems.length > 0 ? 100 : 0;

  const total = subtotal + shipping;

  const totalItems = cartItems.reduce(
    (total, item) => total + item.quantity,
    0
  );

  return (
    <main className="cart-page">

      <section className="cart-header">

        <p className="section-label">
          [ YOUR THRIFTLOOP BAG ]
        </p>

        <h1>
          YOUR
          <br />
          BAG.
        </h1>

      </section>

      <section className="cart-content">

        <div className="cart-items">

          {cartItems.length === 0 ? (

            <div className="empty-cart">

              <p className="section-label">
                [ YOUR BAG IS EMPTY ]
              </p>

              <h2>
                NOTHING HERE
                <br />
                YET.
              </h2>

              <p>
                Find something you love and
                add it to your ThriftLoop bag.
              </p>

              <button
                className="shop-button"
                onClick={() => navigate("products")}
              >
                START SHOPPING →
              </button>

            </div>

          ) : (

            cartItems.map((item) => (

              <div
                className="cart-item"
                key={item.id}
              >

                <div className="cart-item-image">

                  {item.image_url && (
                    <img
                      className="product-photo"
                      src={item.image_url}
                      alt={item.name}
                    />
                  )}

                  <span>
                    #{item.id}
                  </span>

                  {!item.image_url && (
                    <strong>
                      {item.type}
                    </strong>
                  )}

                </div>

                <div className="cart-item-details">

                  <p className="product-category">
                    {item.category}
                  </p>

                  <h2>
                    {item.name}
                  </h2>

                  <p className="cart-price">
                    ₱{item.price.toLocaleString()}
                  </p>

                  <div className="quantity">

                    <button
                      onClick={() =>
                        decreaseQuantity(item.id)
                      }
                    >
                      -
                    </button>

                    <span>
                      {item.quantity}
                    </span>

                    <button
                      onClick={() =>
                        increaseQuantity(item.id)
                      }
                    >
                      +
                    </button>

                  </div>

                  <button
                    className="remove-button"
                    onClick={() =>
                      removeFromCart(item.id)
                    }
                  >
                    REMOVE
                  </button>

                </div>

              </div>

            ))

          )}

        </div>

        <aside className="cart-summary">

          <p className="section-label">
            [ ORDER SUMMARY ]
          </p>

          <div className="summary-row">

            <span>
              ITEMS
            </span>

            <span>
              {totalItems}
            </span>

          </div>

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
              ₱{shipping.toLocaleString()}
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

          <button
            className="checkout-button"
            disabled={cartItems.length === 0}
            onClick={() => navigate("checkout")}
          >
            PROCEED TO CHECKOUT →
          </button>

        </aside>

      </section>

    </main>
  );
}

export default Cart;