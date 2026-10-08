import { useState } from "react";

import Navbar from "./components/Navbar";
import { CartProvider } from "./components/CartContext";
import PaymentReturnHandler from "./components/PaymentReturnHandler";
import ChatBot from "./components/ChatBot";

import Home from "./pages/Home";
import Products from "./pages/Products";
import Cart from "./pages/Cart";
import Checkout from "./pages/Checkout";
import Orders from "./pages/Orders";
import Login from "./pages/Login";
import Register from "./pages/Register";
import AdminOrders from "./components/AdminOrders";
import AdminInventory from "./components/AdminInventory";
import AdminAddProduct from "./components/AdminAddProduct";

function App() {

  const [page, setPage] = useState("home");

  const [user, setUser] = useState(() => {

    const savedUser = localStorage.getItem(
      "thriftloop_user"
    );

    return savedUser
      ? JSON.parse(savedUser)
      : null;
  });

  // Pages where orders can be placed — admins manage stock and orders
  // through the admin panel, they never shop.
  const SHOP_PAGES = ["products", "cart", "checkout", "orders"];

  const navigate = (newPage) => {

    if (user?.role === "admin" && SHOP_PAGES.includes(newPage)) {

      setPage("admin");

      window.scrollTo(0, 0);

      return;
    }

    setPage(newPage);

    window.scrollTo(0, 0);
  };

  return (
    <CartProvider>

      <Navbar
        navigate={navigate}
        user={user}
        setUser={setUser}
      />

      <PaymentReturnHandler
        user={user}
        navigate={navigate}
      />

      {page !== "admin" && <ChatBot />}

      {page === "home" && (
        <Home navigate={navigate} />
      )}

      {page === "products" && (
        <Products navigate={navigate} />
      )}

      {page === "cart" && (
        <Cart navigate={navigate} />
      )}

      {page === "checkout" && (
        <Checkout
          navigate={navigate}
          user={user}
        />
      )}

      {page === "orders" && (
        <Orders
          navigate={navigate}
          user={user}
        />
      )}

      {page === "login" && (
        <Login
          navigate={navigate}
          setUser={setUser}
        />
      )}

      {page === "register" && (
        <Register
          navigate={navigate}
        />
      )}

      {page === "admin" && user?.role === "admin" && (

        <main className="admin-page">

          <section className="admin-header">

            <p className="section-label">
              [ ADMIN PANEL ]
            </p>

            <h1>
              THRIFTLOOP
              <br />
              ADMIN.
            </h1>

            <p>
              Welcome, {user.first_name}.
            </p>

          </section>

          <section className="admin-content">

            <div className="admin-card">

              <h2>
                PRODUCTS
              </h2>

              <p>
                Manage ThriftLoop products
                and inventory.
              </p>

            </div>

            <div className="admin-card">

              <h2>
                ORDERS
              </h2>

              <p>
                View and manage customer orders.
              </p>

            </div>

            <div className="admin-card">

              <h2>
                CUSTOMERS
              </h2>

              <p>
                Manage customer accounts.
              </p>

            </div>

          </section>

          <AdminOrders />

          <AdminInventory />

          <AdminAddProduct />

        </main>

      )}

    </CartProvider>
  );
}

export default App;
