import { useEffect, useRef, useState } from "react";

import { GOOGLE_CLIENT_ID } from "../services/api";


// Google Identity Services script: load once, reuse across mounts.
let gsiScriptPromise = null;

function loadGsiScript() {

  if (window.google?.accounts?.id) {
    return Promise.resolve();
  }

  if (gsiScriptPromise) {
    return gsiScriptPromise;
  }

  gsiScriptPromise = new Promise((resolve, reject) => {

    const script = document.createElement("script");

    script.src = "https://accounts.google.com/gsi/client";

    script.async = true;

    script.defer = true;

    script.onload = resolve;

    script.onerror = reject;

    document.head.appendChild(script);
  });

  return gsiScriptPromise;
}

function Login({ navigate, setUser }) {
  const [loginType, setLoginType] = useState("customer");

  const [email, setEmail] = useState("");

  const [password, setPassword] = useState("");

  const [loading, setLoading] = useState(false);

  const [error, setError] = useState("");

  const googleButtonRef = useRef(null);

  const googleCallbackRef = useRef(null);

  const handleLogin = async (event) => {
    event.preventDefault();

    setError("");

    if (!email || !password) {
      setError("PLEASE ENTER YOUR EMAIL AND PASSWORD.");
      return;
    }

    setLoading(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/auth/login.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            email: email,
            password: password
          })
        }
      );

      const data = await response.json();

      if (!data.success) {
        setError(
          data.message || "LOGIN FAILED."
        );

        return;
      }

      if (data.user.role !== loginType) {
        setError(
          `THIS ACCOUNT IS NOT REGISTERED AS A ${loginType.toUpperCase()}.`
        );

        return;
      }

      localStorage.setItem(
        "thriftloop_user",
        JSON.stringify(data.user)
      );

      setUser(data.user);

      if (data.user.role === "admin") {
        navigate("admin");
      } else {
        navigate("home");
      }

    } catch (error) {
      console.error(error);

      setError(
        "CANNOT CONNECT TO THRIFTLOOP SERVER."
      );

    } finally {
      setLoading(false);
    }
  };

  // Called by Google Identity Services with the ID token.
  const handleGoogleCredential = async (credential) => {

    setError("");

    setLoading(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/auth/google.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({ credential: credential })
        }
      );

      const data = await response.json();

      if (!data.success) {
        setError(
          data.message || "GOOGLE LOGIN FAILED."
        );

        return;
      }

      if (data.user.role !== loginType) {
        setError(
          `THIS ACCOUNT IS NOT REGISTERED AS A ${loginType.toUpperCase()}.`
        );

        return;
      }

      localStorage.setItem(
        "thriftloop_user",
        JSON.stringify(data.user)
      );

      setUser(data.user);

      if (data.user.role === "admin") {
        navigate("admin");
      } else {
        navigate("home");
      }

    } catch (error) {
      console.error(error);

      setError(
        "CANNOT CONNECT TO THRIFTLOOP SERVER."
      );

    } finally {
      setLoading(false);
    }
  };

  // Keep the latest handler available to the GIS iframe callback.
  useEffect(() => {
    googleCallbackRef.current = handleGoogleCredential;
  });

  useEffect(() => {

    if (loginType !== "customer" || !googleButtonRef.current) {
      return;
    }

    let cancelled = false;

    loadGsiScript()
      .then(() => {

        if (cancelled || !googleButtonRef.current) {
          return;
        }

        if (!window.google?.accounts?.id) {
          return;
        }

        window.google.accounts.id.initialize({
          client_id: GOOGLE_CLIENT_ID,

          callback: (response) =>
            googleCallbackRef.current(response.credential)
        });

        // Clear so StrictMode's double effect run doesn't stack buttons.
        googleButtonRef.current.innerHTML = "";

        // Fit the button to the available width (mobile boxes are narrow).
        const availableWidth =
          googleButtonRef.current.clientWidth || 300;

        window.google.accounts.id.renderButton(
          googleButtonRef.current,
          {
            theme: "outline",
            size: "large",
            shape: "rectangular",
            text: "continue_with",
            width: Math.min(availableWidth, 400)
          }
        );
      })
      .catch((error) => {
        console.error("Could not load Google Sign In.", error);

        if (!cancelled) {
          setError("COULD NOT LOAD GOOGLE SIGN IN.");
        }
      });

    return () => {
      cancelled = true;
    };

  }, [loginType]);

  return (
    <main className="login-page">

      <section className="login-wrapper">

        <div className="login-intro">

          <p className="section-label">
            [ THRIFTLOOP ACCOUNT ]
          </p>

          <h1>
            WELCOME
            <br />
            BACK.
          </h1>

          <p className="login-description">
            SIGN IN TO CONTINUE
            <br />
            YOUR THRIFTLOOP JOURNEY.
          </p>

          <div className="login-stamp">
            PRE-LOVED
            <br />
            RE-LOVED
            <br />
            RE-WORN
          </div>

        </div>

        <div className="login-box">

          <div className="login-box-header">

            <p className="section-label">
              [ LOG IN ]
            </p>

            <h2>
              YOUR
              <br />
              ACCOUNT.
            </h2>

          </div>

          <div className="login-type">

            <button
              type="button"
              className={
                loginType === "customer"
                  ? "active"
                  : ""
              }
              onClick={() => {
                setLoginType("customer");
                setError("");
              }}
            >
              CUSTOMER
            </button>

            <button
              type="button"
              className={
                loginType === "admin"
                  ? "active"
                  : ""
              }
              onClick={() => {
                setLoginType("admin");
                setError("");
              }}
            >
              ADMIN
            </button>

          </div>

          <form
            className="login-form"
            onSubmit={handleLogin}
          >

            <div className="login-field">

              <label>
                EMAIL ADDRESS
              </label>

              <input
                type="email"
                placeholder={
                  loginType === "admin"
                    ? "admin@thriftloop.com"
                    : "you@example.com"
                }
                value={email}
                onChange={(event) =>
                  setEmail(event.target.value)
                }
              />

            </div>

            <div className="login-field">

              <label>
                PASSWORD
              </label>

              <input
                type="password"
                placeholder="ENTER YOUR PASSWORD"
                value={password}
                onChange={(event) =>
                  setPassword(event.target.value)
                }
              />

            </div>

            {error && (
              <div className="login-error">
                {error}
              </div>
            )}

            <button
              className="login-submit"
              type="submit"
              disabled={loading}
            >
              {loading
                ? "LOGGING IN..."
                : loginType === "admin"
                  ? "ADMIN LOGIN →"
                  : "LOG IN →"}
            </button>

          </form>

          {loginType === "customer" && (

            <div className="login-google">

              <div className="login-google-divider">
                <span>OR</span>
              </div>

              <div
                ref={googleButtonRef}
                className="login-google-button"
              />

            </div>

          )}

          {loginType === "customer" && (

            <div className="login-register">

              <p>
                NEW TO THRIFTLOOP?
              </p>

              <button
                type="button"
                onClick={() =>
                  navigate("register")
                }
              >
                CREATE CUSTOMER ACCOUNT →
              </button>

            </div>

          )}

          {loginType === "admin" && (

            <div className="admin-login-note">

              <span>
                ADMIN ACCESS
              </span>

              <p>
                FOR AUTHORIZED THRIFTLOOP
                ADMINISTRATORS ONLY.
              </p>

            </div>

          )}

        </div>

      </section>

    </main>
  );
}

export default Login;