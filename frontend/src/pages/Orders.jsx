import AIStylist from "../components/AIStylist";
import { useEffect, useState } from "react";

function Orders({ user }) {
  const [orders, setOrders] = useState([]);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState("");

  const [expandedOrders, setExpandedOrders] = useState({});

  const [cancelTarget, setCancelTarget] = useState(null);


  useEffect(() => {

    if (!user?.email) {
      setLoading(false);
      return;
    }


    let active = true;


    const loadOrders = async () => {
      try {
        const response = await fetch(
          "http://localhost/thriftloop/api/orders/list.php?email=" +
            encodeURIComponent(user.email)
        );

        const data = await response.json();


        if (!active) return;


        if (!data.success) {
          setError(
            data.message || "FAILED TO LOAD ORDERS."
          );

          return;
        }


        setOrders(
          data.orders.map((order) => ({
            id: order.order_number,
              courier: order.courier || "",

              trackingNumber: order.tracking_number || "",

            date: new Date(order.created_at)
              .toLocaleDateString("en-US", {
                month: "long",
                day: "numeric",
                year: "numeric",
              })
              .toUpperCase(),

            status: order.order_status.toUpperCase(),

            paymentMethod: (order.payment_method || "online").toUpperCase(),

            payment:
              (order.payment_status || "pending") === "pending" &&
              (order.payment_method || "online") === "cod"
                ? "COD"
                : (order.payment_status || "pending").toUpperCase(),

            total: order.total,

            items: order.items.map((item) => ({
            name: item.product_name,

            quantity: item.quantity,

            price: item.price,

            image: item.image_url,

            category: item.category || "",

            description: item.description || "",
          })),
          }))
        );


        // SAFETY NET: if the browser never made it back from PayMongo
        // (closed tab, dropped redirect), re-verify pending online orders
        // on every visit so the payment badge self-heals.
        data.orders
          .filter(
            (order) =>
              order.order_status !== "cancelled" &&
              (order.payment_status || "pending") === "pending" &&
              (order.payment_method || "online") === "online" &&
              String(order.payment_reference || "").startsWith("cs_")
          )
          .forEach((order) => {
            fetch(
              "http://localhost/thriftloop/api/payments/return.php",
              {
                method: "POST",

                headers: {
                  "Content-Type": "application/json"
                },

                body: JSON.stringify({
                  order_number: order.order_number,

                  email: user.email,

                  action: "success",
                }),
              }
            )
              .then((response) => response.json())
              .then((result) => {
                if (!active) return;

                if (result.payment_status === "paid") {
                  setOrders((current) =>
                    current.map((item) =>
                      item.id === order.order_number
                        ? { ...item, payment: "PAID" }
                        : item
                    )
                  );
                }
              })
              .catch(() => {
                // Best effort — the badge stays pending until the next visit.
              });
          });

      } catch {
        if (active) {
          setError(
            "CANNOT REACH THE THRIFTLOOP SERVER."
          );
        }

      } finally {
        if (active) {
          setLoading(false);
        }
      }
    };


    loadOrders();


    return () => {
      active = false;
    };

  }, [user?.email]);


  const toggleOrder = (orderId) => {
    setExpandedOrders((current) => ({
      ...current,

      [orderId]: !current[orderId],
    }));
  };


  const confirmCancel = async (orderId) => {
    setError("");


    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/orders/cancel.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            order_number: orderId,

            email: user.email,
          }),
        }
      );

      const data = await response.json();


      if (!data.success) {
        setError(
          data.message || "FAILED TO CANCEL THE ORDER."
        );

        setCancelTarget(null);
        return;
      }


      setOrders((currentOrders) =>
        currentOrders.map((order) =>
          order.id === orderId
            ? {
                ...order,
                status: "CANCELLED",
                payment: (
                  data.order?.payment_status || "cancelled"
                ).toUpperCase(),
              }
            : order
        )
      );

      setCancelTarget(null);

    } catch {
      setError(
        "CANNOT REACH THE THRIFTLOOP SERVER."
      );

      setCancelTarget(null);
    }
  };


  return (
    <main className="orders-page">

      <section className="orders-header">

        <p className="section-label">
          [ THRIFTLOOP ACCOUNT ]
        </p>

        <h1>
          MY
          <br />
          ORDERS.
        </h1>

        <p className="orders-description">
          KEEP TRACK OF YOUR PRE-LOVED
          <br />
          PIECES AND THEIR JOURNEY.
        </p>

      </section>


      <section className="orders-content">

        <div className="orders-top">

          <p className="section-label">
            [ ORDER HISTORY ]
          </p>

          <span>
            {orders.length} ORDERS
          </span>

        </div>


        <div className="orders-list">

          {!user && (
            <p className="section-label">
              [ PLEASE SIGN IN TO VIEW YOUR ORDERS. ]
            </p>
          )}


          {loading && user && (
            <p className="section-label">
              [ LOADING YOUR ORDERS... ]
            </p>
          )}


          {error && (
            <div className="login-error">
              {error}
            </div>
          )}


          {!loading && !error && user && orders.length === 0 && (
            <p className="section-label">
              [ NO ORDERS YET — YOUR FIRST FIND IS WAITING. ]
            </p>
          )}

          {orders.map((order) => (

            <article
              className="order-card"
              key={order.id}
            >

              <div className="order-top">

                <div>

                  <span className="order-label">
                    ORDER NUMBER
                  </span>

                  <h2>
                    {order.id}
                  </h2>

                </div>


                <div className="order-status">

                  <span className="order-label">
                    STATUS
                  </span>

                  <strong
                    className={
                      order.status === "DELIVERED"
                        ? "status-delivered"
                        : order.status === "CANCELLED"
                          ? "status-cancelled"
                          : "status-processing"
                    }
                  >
                    {order.status}
                  </strong>


                  <span className="order-label">
                    PAYMENT
                  </span>

                  <strong
                    className={
                      order.payment === "PAID"
                        ? "status-delivered"
                        : order.payment === "PENDING" ||
                          order.payment === "COD"
                          ? "status-processing"
                          : "status-cancelled"
                    }
                  >
                    {order.payment}
                  </strong>

                </div>

              </div>


              <div className="order-details">

                <div>

                  <span className="order-label">
                    ORDER DATE
                  </span>

                  <p>
                    {order.date}
                  </p>

                </div>


                <div>

                  <span className="order-label">
                    ITEMS
                  </span>

                  <p>
                    {order.items.length} PIECE
                    {order.items.length > 1 ? "S" : ""}
                  </p>

                </div>


                <div>

                  <span className="order-label">
                    TOTAL
                  </span>

                  <p>
                    ₱{order.total.toLocaleString()}
                  </p>

                </div>
                    
                <div>
                    <span className="order-label">
                      COURIER
                    </span>

                    <p>
                      {order.courier || "NOT YET ASSIGNED"}
                    </p>
                </div>

                <div>
                  <span className="order-label">
                    TRACKING ID
                  </span>

                  <p>
                    {order.trackingNumber || "NOT YET AVAILABLE"}
                  </p>
                </div>

              </div>


              {expandedOrders[order.id] && (

                <div className="order-items">

                  {order.items.map((item, index) => (

                    <div
                      className="order-item"
                      key={index}
                    >

                      <div className="order-item-image">

                        {item.image && (
                          <img
                            className="product-photo"
                            src={item.image}
                            alt={item.name}
                          />
                        )}

                        <span>
                          #{String(index + 1).padStart(2, "0")}
                        </span>
                      </div>


                      <div className="order-item-info">

                        <h3>
                          {item.name}
                        </h3>

                        <span>
                          QTY: {item.quantity}
                        </span>

                        <AIStylist product={item} />

                      </div>


                      <strong className="order-item-price">
                        ₱{item.price.toLocaleString()}
                      </strong>

                    </div>

                  ))}

                </div>

              )}


              <div className="order-footer">

                <span>
                  THANK YOU FOR GIVING THESE PIECES
                  A SECOND LIFE.
                </span>

                <div className="order-actions">

                  <button
                    onClick={() => toggleOrder(order.id)}
                  >
                    {expandedOrders[order.id]
                      ? "HIDE DETAILS ↑"
                      : "VIEW ORDER →"}
                  </button>


                  {order.status === "PENDING" &&
                    cancelTarget !== order.id && (
                      <button
                        className="order-cancel"
                        onClick={() =>
                          setCancelTarget(order.id)
                        }
                      >
                        CANCEL ORDER
                      </button>
                    )}


                  {order.status === "PENDING" &&
                    cancelTarget === order.id && (
                      <>
                        <button
                          className="order-cancel order-cancel-confirm"
                          onClick={() =>
                            confirmCancel(order.id)
                          }
                        >
                          YES, CANCEL IT
                        </button>

                        <button
                          className="order-keep"
                          onClick={() =>
                            setCancelTarget(null)
                          }
                        >
                          KEEP IT
                        </button>
                      </>
                  )}

                </div>

              </div>

            </article>

          ))}

        </div>

      </section>

    </main>
  );
}

export default Orders;
