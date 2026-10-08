import { useEffect, useState } from "react";

const ORDER_STATUSES = [
  "pending",
  "processing",
  "packed",
  "shipped",
  "out_for_delivery",
  "delivered",
  "cancelled",
];

const PAYMENT_STATUSES = [
  "pending",
  "paid",
  "failed",
  "cancelled",
  "refunded",
];

function AdminOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [saving, setSaving] = useState(false);
  const [shippingData, setShippingData] = useState({});

  // =========================
  // LOAD ORDERS
  // =========================
  const loadOrders = async () => {
    try {
      setLoading(true);
      setError("");

      const response = await fetch(
        "http://localhost/thriftloop/api/orders/list.php?all=1"
      );

      if (!response.ok) {
        throw new Error(
          `Server returned ${response.status}`
        );
      }

      const data = await response.json();

      if (!data.success) {
        setError(
          data.message || "FAILED TO LOAD ORDERS."
        );
        return;
      }

      const fetchedOrders = data.orders || [];

      setOrders(fetchedOrders);

      const shipping = {};

      fetchedOrders.forEach((order) => {
        shipping[order.id] = {
          courier: order.courier || "",
          tracking_number:
            order.tracking_number || "",
        };
      });

      setShippingData(shipping);

    } catch (err) {
      console.error("LOAD ORDERS ERROR:", err);

      setError(
        "CANNOT REACH THE THRIFTLOOP SERVER. CHECK YOUR API URL AND XAMPP."
      );

    } finally {
      setLoading(false);
    }
  };

  // =========================
  // UPDATE ORDER
  // =========================
  const updateOrder = async (orderId, patch) => {
    setError("");
    setSaving(true);

    try {

      // IMPORTANT:
      // This MUST be update.php, NOT list.php
      const response = await fetch(
        "http://localhost/thriftloop/api/orders/update.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json",
          },

          body: JSON.stringify({
            order_id: orderId,
            ...patch,
          }),
        }
      );

      if (!response.ok) {
        throw new Error(
          `Server returned ${response.status}`
        );
      }

      const data = await response.json();

      console.log("UPDATE RESPONSE:", data);

      if (!data.success) {
        setError(
          data.message ||
            "FAILED TO UPDATE ORDER."
        );
        return;
      }

      // Update order in the screen
      setOrders((currentOrders) =>
        currentOrders.map((order) =>
          order.id === orderId
            ? {
                ...order,
                ...(data.order || {}),
                ...patch,
              }
            : order
        )
      );

      // Update shipping information
      setShippingData((current) => ({
        ...current,

        [orderId]: {
          courier:
            data.order?.courier ??
            patch.courier ??
            current[orderId]?.courier ??
            "",

          tracking_number:
            data.order?.tracking_number ??
            patch.tracking_number ??
            current[orderId]?.tracking_number ??
            "",
        },
      }));

    } catch (error) {

      console.error(
        "UPDATE ORDER ERROR:",
        error
      );

      setError(
        "ERROR: " + error.message
      );

    } finally {
      setSaving(false);
    }
  };

  // =========================
  // SAVE SHIPPING INFORMATION
  // =========================
  const saveShippingInfo = async (orderId) => {

    const shipping =
      shippingData[orderId] || {};

    const courier =
      shipping.courier?.trim() || "";

    const trackingNumber =
      shipping.tracking_number?.trim() || "";

    if (!courier) {
      setError(
        "PLEASE ENTER THE COURIER."
      );
      return;
    }

    if (!trackingNumber) {
      setError(
        "PLEASE ENTER THE TRACKING NUMBER."
      );
      return;
    }

    await updateOrder(orderId, {
      courier: courier,
      tracking_number: trackingNumber,
    });
  };

  // =========================
  // LOAD ORDERS ON PAGE LOAD
  // =========================
  useEffect(() => {
    loadOrders();
  }, []);

  // =========================
  // PAGE
  // =========================
  return (
    <>
      <section className="admin-header">

        <p className="section-label">
          [ ORDER QUEUE ]
        </p>

        {loading && (
          <p className="section-label">
            [ LOADING ORDERS... ]
          </p>
        )}

        {!loading &&
          !error &&
          orders.length === 0 && (
            <p className="section-label">
              [ NO ORDERS YET. ]
            </p>
          )}

        {error && (
          <div className="login-error">
            {error}
          </div>
        )}

      </section>

      {orders.length > 0 && (

        <div className="admin-content">

          {orders.map((order) => (

            <div
              className="admin-card"
              key={order.order_number}
            >

              {/* =========================
                  ORDER INFORMATION
              ========================= */}

              <h2>
                {order.order_number}
              </h2>

              <p>
                {order.first_name}{" "}
                {order.last_name}

                <br />

                {order.email}

                <br />

                {order.items
                  ?.map(
                    (item) =>
                      `${item.product_name} ×${item.quantity}`
                  )
                  .join(", ")}

                <br />

                {order.items?.length || 0} PIECE
                {(order.items?.length || 0) > 1
                  ? "S"
                  : ""}{" "}
                — ₱
                {Number(
                  order.total || 0
                ).toLocaleString()}
              </p>

              {/* =========================
                  ORDER STATUS
              ========================= */}

              <div className="form-group">

                <label>
                  ORDER STATUS
                </label>

                <select
                  value={
                    order.order_status
                  }
                  disabled={
                    saving ||
                    order.order_status ===
                      "delivered" ||
                    order.order_status ===
                      "cancelled"
                  }
                  onChange={(event) =>
                    updateOrder(
                      order.id,
                      {
                        order_status:
                          event.target.value,
                      }
                    )
                  }
                >

                  {ORDER_STATUSES.map(
                    (status) => (

                      <option
                        key={status}
                        value={status}
                      >
                        {status
                          .replace(
                            /_/g,
                            " "
                          )
                          .toUpperCase()}
                      </option>

                    )
                  )}

                </select>

              </div>

              {/* =========================
                  PAYMENT STATUS
              ========================= */}

              <div className="form-group">

                <label>
                  PAYMENT STATUS
                </label>

                <select
                  value={
                    order.payment_status
                  }
                  disabled={
                    saving ||
                    order.order_status ===
                      "delivered" ||
                    (order.order_status ===
                      "cancelled" &&
                      order.payment_status ===
                        "refunded")
                  }
                  onChange={(event) =>
                    updateOrder(
                      order.id,
                      {
                        payment_status:
                          event.target.value,
                      }
                    )
                  }
                >

                  {PAYMENT_STATUSES.map(
                    (status) => (

                      <option
                        key={status}
                        value={status}
                      >
                        {status
                          .replace(
                            /_/g,
                            " "
                          )
                          .toUpperCase()}
                      </option>

                    )
                  )}

                </select>

              </div>

              {/* =========================
                  COURIER
              ========================= */}

              <div className="form-group">

                <label>
                  COURIER
                </label>

                <input
                  type="text"
                  placeholder="ENTER COURIER"
                  value={
                    shippingData[
                      order.id
                    ]?.courier || ""
                  }
                  disabled={
                    saving ||
                    order.order_status ===
                      "delivered" ||
                    order.order_status ===
                      "cancelled"
                  }
                  onChange={(event) =>
                    setShippingData(
                      (current) => ({
                        ...current,

                        [order.id]: {
                          ...current[
                            order.id
                          ],

                          courier:
                            event.target.value,
                        },
                      })
                    )
                  }
                />

              </div>

              {/* =========================
                  TRACKING NUMBER
              ========================= */}

              <div className="form-group">

                <label>
                  TRACKING NUMBER
                </label>

                <input
                  type="text"
                  placeholder="ENTER TRACKING NUMBER"
                  value={
                    shippingData[
                      order.id
                    ]?.tracking_number || ""
                  }
                  disabled={
                    saving ||
                    order.order_status ===
                      "delivered" ||
                    order.order_status ===
                      "cancelled"
                  }
                  onChange={(event) =>
                    setShippingData(
                      (current) => ({
                        ...current,

                        [order.id]: {
                          ...current[
                            order.id
                          ],

                          tracking_number:
                            event.target.value,
                        },
                      })
                    )
                  }
                />

              </div>

              {/* =========================
                  SAVE SHIPPING
              ========================= */}

              <button
                type="button"
                disabled={
                  saving ||
                  order.order_status ===
                    "delivered" ||
                  order.order_status ===
                    "cancelled"
                }
                onClick={() =>
                  saveShippingInfo(
                    order.id
                  )
                }
              >

                {saving
                  ? "SAVING..."
                  : "SAVE SHIPPING INFO"}

              </button>

              {/* =========================
                  CURRENT SHIPPING INFO
              ========================= */}

              {order.courier &&
                order.tracking_number && (

                  <p className="section-label">

                    [ COURIER:{" "}
                    {order.courier} ]

                    <br />

                    [ TRACKING:{" "}
                    {order.tracking_number} ]

                  </p>

                )}

              {/* =========================
                  CLOSED ORDER
              ========================= */}

              {(order.order_status ===
                "delivered" ||
                (order.order_status ===
                  "cancelled" &&
                  order.payment_status ===
                    "refunded")) && (

                <p className="section-label">
                  [ ORDER CLOSED ]
                </p>

              )}

              {order.order_status ===
                "cancelled" &&
                order.payment_status !==
                  "refunded" && (

                <p className="section-label">

                  [ ORDER CLOSED · PAYMENT EDITABLE ]

                </p>

              )}

            </div>

          ))}

        </div>

      )}

    </>
  );
}

export default AdminOrders;