function Navbar({ navigate, user, setUser }) {

  const handleLogout = () => {

    localStorage.removeItem(
      "thriftloop_user"
    );

    setUser(null);

    navigate("home");
  };

  return (
    <header className="navbar">

      <div className="nav-top">

        <button
          className="logo"
          onClick={() => navigate("home")}
        >
          THRIFT<span>LOOP</span>
        </button>

        <div className="nav-note">
          PRE-LOVED / RE-LOVED / RE-WORN
        </div>

      </div>

      <nav className="nav-links">

        <button
          onClick={() => navigate("home")}
        >
          HOME
        </button>

        {(!user || user.role !== "admin") && (
          <button
            onClick={() => navigate("products")}
          >
            SHOP
          </button>
        )}

        {user &&
          user.role === "customer" && (
            <>
              <button
                onClick={() => navigate("orders")}
              >
                MY ORDERS
              </button>

              <button
                onClick={() => navigate("cart")}
              >
                CART
              </button>
            </>
          )}

        {user &&
          user.role === "admin" && (

            <button
              onClick={() => navigate("admin")}
            >
              ADMIN
            </button>

          )}

        {!user && (

          <button
            onClick={() => navigate("login")}
          >
            LOG IN
          </button>

        )}

        {user && (

          <button
            onClick={handleLogout}
          >
            LOG OUT
          </button>

        )}

      </nav>

    </header>
  );
}

export default Navbar;