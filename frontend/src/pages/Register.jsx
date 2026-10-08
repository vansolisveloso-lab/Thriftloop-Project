import { useState } from "react";

function Register({ navigate }) {
  const [firstName, setFirstName] = useState("");

  const [lastName, setLastName] = useState("");

  const [email, setEmail] = useState("");

  const [password, setPassword] = useState("");

  const [confirmPassword, setConfirmPassword] = useState("");

  const [loading, setLoading] = useState(false);

  const [error, setError] = useState("");

  const [success, setSuccess] = useState("");

  const handleRegister = async (event) => {
    event.preventDefault();

    setError("");
    setSuccess("");

    if (
      !firstName ||
      !lastName ||
      !email ||
      !password ||
      !confirmPassword
    ) {
      setError("PLEASE COMPLETE ALL FIELDS.");
      return;
    }

    if (password !== confirmPassword) {
      setError("PASSWORDS DO NOT MATCH.");
      return;
    }

    if (password.length < 6) {
      setError(
        "PASSWORD MUST BE AT LEAST 6 CHARACTERS."
      );

      return;
    }

    setLoading(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/auth/register.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            first_name: firstName,
            last_name: lastName,
            email: email,
            password: password
          })
        }
      );

      const data = await response.json();

      if (!data.success) {
        setError(
          data.message || "REGISTRATION FAILED."
        );

        return;
      }

      setSuccess(
        "ACCOUNT CREATED SUCCESSFULLY! YOU CAN NOW LOG IN."
      );

      setFirstName("");
      setLastName("");
      setEmail("");
      setPassword("");
      setConfirmPassword("");

    } catch (error) {
      console.error(error);

      setError(
        "CANNOT CONNECT TO THRIFTLOOP SERVER."
      );

    } finally {
      setLoading(false);
    }
  };

  return (
    <main className="auth-page">

      <section className="auth-container">

        <div className="auth-header">

          <p className="section-label">
            [ JOIN THRIFTLOOP ]
          </p>

          <h1>
            CREATE
            <br />
            ACCOUNT.
          </h1>

        </div>

        <form
          className="auth-form"
          onSubmit={handleRegister}
        >

          <div className="form-group">

            <label>
              FIRST NAME
            </label>

            <input
              type="text"
              value={firstName}
              onChange={(event) =>
                setFirstName(event.target.value)
              }
              placeholder="First name"
            />

          </div>

          <div className="form-group">

            <label>
              LAST NAME
            </label>

            <input
              type="text"
              value={lastName}
              onChange={(event) =>
                setLastName(event.target.value)
              }
              placeholder="Last name"
            />

          </div>

          <div className="form-group">

            <label>
              EMAIL
            </label>

            <input
              type="email"
              value={email}
              onChange={(event) =>
                setEmail(event.target.value)
              }
              placeholder="you@example.com"
            />

          </div>

          <div className="form-group">

            <label>
              PASSWORD
            </label>

            <input
              type="password"
              value={password}
              onChange={(event) =>
                setPassword(event.target.value)
              }
              placeholder="Minimum 6 characters"
            />

          </div>

          <div className="form-group">

            <label>
              CONFIRM PASSWORD
            </label>

            <input
              type="password"
              value={confirmPassword}
              onChange={(event) =>
                setConfirmPassword(event.target.value)
              }
              placeholder="Confirm password"
            />

          </div>

          {error && (
            <div className="auth-error">
              {error}
            </div>
          )}

          {success && (
            <div className="auth-success">
              {success}
            </div>
          )}

          <button
            className="checkout-button"
            type="submit"
            disabled={loading}
          >
            {loading
              ? "CREATING ACCOUNT..."
              : "CREATE ACCOUNT →"}
          </button>

        </form>

        <div className="auth-register">

          <p>
            ALREADY HAVE AN ACCOUNT?
          </p>

          <button
            onClick={() => navigate("login")}
          >
            LOG IN →
          </button>

        </div>

      </section>

    </main>
  );
}

export default Register;