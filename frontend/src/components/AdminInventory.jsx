import { useEffect, useRef, useState } from "react";

function StockCard({ product, onResult, onRemoved }) {
  const [quantity, setQuantity] = useState("1");

  const [adding, setAdding] = useState(false);

  const [imageFile, setImageFile] = useState(null);

  const [uploading, setUploading] = useState(false);

  const [removing, setRemoving] = useState(false);

  const imageInputRef = useRef(null);


  const handleAdd = async () => {
    const amount = parseInt(quantity, 10);


    if (!amount || amount < 1) {
      onResult(product.id, null, "QUANTITY MUST BE AT LEAST 1.");
      return;
    }

    setAdding(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/products/update_stock.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            product_id: product.id,
            quantity: amount,
          }),
        }
      );

      const data = await response.json();


      if (!data.success) {
        onResult(
          product.id,
          null,
          data.message || "FAILED TO UPDATE STOCK."
        );

        return;
      }


      onResult(product.id, { stock: data.product.stock }, "");

      setQuantity("1");

    } catch {
      onResult(
        product.id,
        null,
        "CANNOT REACH THE THRIFTLOOP SERVER."
      );

    } finally {
      setAdding(false);
    }
  };


  const handleRemove = async () => {

    const confirmed = window.confirm(
      `REMOVE "${product.name}"?\n\n` +
      "THIS WILL TAKE IT OFF THE SHOP." +
      (product.stock > 0
        ? `\n\nCURRENT STOCK: ${product.stock} PIECE(S) WILL BE LOST.`
        : "")
    );


    if (!confirmed) return;


    setRemoving(true);


    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/products/delete.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            product_id: product.id,
          }),
        }
      );

      const data = await response.json();


      if (!data.success) {
        onResult(
          product.id,
          null,
          data.message || "FAILED TO REMOVE PRODUCT."
        );

        return;
      }


      onRemoved(product.id, data.message);

    } catch {
      onResult(
        product.id,
        null,
        "CANNOT REACH THE THRIFTLOOP SERVER."
      );

    } finally {
      setRemoving(false);
    }
  };


  const handleUploadImage = async () => {

    if (!imageFile) {
      onResult(product.id, null, "CHOOSE AN IMAGE FILE FIRST.");
      return;
    }

    setUploading(true);

    try {
      const formData = new FormData();

      formData.append("product_id", product.id);

      formData.append("file", imageFile);


      const response = await fetch(
        "http://localhost/thriftloop/api/products/upload_image.php",
        {
          method: "POST",
          body: formData,
        }
      );

      const data = await response.json();


      if (!data.success) {
        onResult(
          product.id,
          null,
          data.message || "FAILED TO UPLOAD IMAGE."
        );

        return;
      }


      onResult(
        product.id,
        { image_url: data.product.image_url },
        ""
      );

      setImageFile(null);

      if (imageInputRef.current) {
        imageInputRef.current.value = "";
      }

    } catch {
      onResult(
        product.id,
        null,
        "CANNOT REACH THE THRIFTLOOP SERVER."
      );

    } finally {
      setUploading(false);
    }
  };


  return (
    <div className="admin-card">

      <h2>
        {product.name}
      </h2>

      <p>
        {product.product_code}
        <br />
        CURRENT STOCK: {product.stock}
        <br />
        IMAGE: {product.image_url || "NONE"}
        <br />
        ₱{product.price.toLocaleString()} — {product.category}
      </p>


      <div className="form-group">
        <label>ADD STOCK</label>

        <input
          type="number"
          min="1"
          max="10000"
          value={quantity}
          disabled={adding || uploading || removing}
          onChange={(event) =>
            setQuantity(event.target.value)
          }
        />
      </div>


      <button
        className="checkout-button"
        onClick={handleAdd}
        disabled={adding || uploading || removing}
      >
        {adding ? "ADDING..." : "ADD STOCK →"}
      </button>


      <div className="form-group">
        <label>PRODUCT IMAGE</label>

        <input
          type="file"
          accept="image/jpeg,image/png,image/webp,image/gif"
          ref={imageInputRef}
          disabled={adding || uploading || removing}
          onChange={(event) =>
            setImageFile(event.target.files[0] || null)
          }
        />
      </div>


      <button
        className="checkout-button"
        onClick={handleUploadImage}
        disabled={adding || uploading || removing}
      >
        {uploading ? "UPLOADING..." : "UPLOAD IMAGE →"}
      </button>


      <button
        className="remove-button"
        onClick={handleRemove}
        disabled={adding || uploading || removing}
      >
        {removing ? "REMOVING..." : "REMOVE PRODUCT"}
      </button>

    </div>
  );
}


function AdminInventory() {
  const [products, setProducts] = useState([]);

  const [loading, setLoading] = useState(true);

  const [error, setError] = useState("");

  const [success, setSuccess] = useState("");


  useEffect(() => {

    let active = true;


    const loadProducts = async () => {
      try {
        const response = await fetch(
          "http://localhost/thriftloop/api/products/list.php"
        );

        const data = await response.json();


        if (!active) return;


        if (!data.success) {
          setError(
            data.message || "FAILED TO LOAD PRODUCTS."
          );

          return;
        }


        setProducts(data.products);

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


    loadProducts();


    return () => {
      active = false;
    };

  }, []);


  const handleResult = (productId, patch, message) => {

    setSuccess("");


    if (message) {
      setError(message);
      return;
    }


    setError("");


    setProducts((currentProducts) =>
      currentProducts.map((product) =>
        product.id === productId
          ? { ...product, ...patch }
          : product
      )
    );
  };


  const handleRemoved = (productId, message) => {

    setError("");

    setSuccess(message || "PRODUCT REMOVED.");


    setProducts((currentProducts) =>
      currentProducts.filter(
        (product) => product.id !== productId
      )
    );
  };


  return (
    <>

      <section className="admin-header">

        <p className="section-label">
          [ INVENTORY ]
        </p>


        {loading && (
          <p className="section-label">
            [ LOADING PRODUCTS... ]
          </p>
        )}


        {!loading && !error && !success && products.length === 0 && (
          <p className="section-label">
            [ NO PRODUCTS YET. ]
          </p>
        )}


        {error && (
          <div className="login-error">
            {error}
          </div>
        )}


        {success && (
          <p className="section-label">
            {success}
          </p>
        )}

      </section>


      {products.length > 0 && (

        <div className="admin-content">

          {products.map((product) => (
            <StockCard
              key={product.id}
              product={product}
              onResult={handleResult}
              onRemoved={handleRemoved}
            />
          ))}

        </div>

      )}

    </>
  );
}

export default AdminInventory;
