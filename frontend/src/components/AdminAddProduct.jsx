import { useRef, useState } from "react";

function AdminAddProduct() {
  const [name, setName] = useState("");

  const [category, setCategory] = useState("");

  const [type, setType] = useState("");

  const [price, setPrice] = useState("");

  const [stock, setStock] = useState("1");

  const [description, setDescription] = useState("");

  const [imageFile, setImageFile] = useState(null);

  const [error, setError] = useState("");

  const [success, setSuccess] = useState("");

  const [saving, setSaving] = useState(false);

  const imageInputRef = useRef(null);


  const resetForm = () => {
    setName("");

    setCategory("");

    setType("");

    setPrice("");

    setStock("1");

    setDescription("");

    setImageFile(null);

    if (imageInputRef.current) {
      imageInputRef.current.value = "";
    }
  };


  const handleAdd = async () => {
    setError("");

    setSuccess("");


    if (!name.trim() || !category.trim()) {
      setError("NAME AND CATEGORY ARE REQUIRED.");
      return;
    }


    if (!price || Number(price) <= 0) {
      setError("ENTER A PRICE GREATER THAN ZERO.");
      return;
    }


    setSaving(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/products/create.php",
        {
          method: "POST",

          headers: {
            "Content-Type": "application/json"
          },

          body: JSON.stringify({
            name: name.trim(),

            category: category.trim(),

            type: type.trim(),

            description: description.trim(),

            price: Number(price),

            stock: parseInt(stock, 10) || 0,
          }),
        }
      );

      const data = await response.json();


      if (!data.success) {
        setError(
          data.message || "FAILED TO ADD PRODUCT."
        );

        return;
      }


      let successText =
        `[ ${data.product.product_code} ADDED — LIVE IN THE SHOP NOW. ]`;


      if (imageFile) {

        const formData = new FormData();

        formData.append("product_id", data.product.id);

        formData.append("file", imageFile);


        try {
          const uploadResponse = await fetch(
            "http://localhost/thriftloop/api/products/upload_image.php",
            {
              method: "POST",
              body: formData,
            }
          );

          const uploadData = await uploadResponse.json();


          if (uploadData.success) {
            successText =
              `[ ${data.product.product_code} ADDED + IMAGE UPLOADED — LIVE IN THE SHOP NOW. ]`;

          } else {
            setError(
              "PRODUCT ADDED, BUT IMAGE FAILED: " +
                (uploadData.message || "UNKNOWN ERROR") +
                " — RETRY FROM THE INVENTORY SECTION."
            );
          }

        } catch {
          setError(
            "PRODUCT ADDED, BUT THE IMAGE UPLOAD COULD NOT REACH THE SERVER — RETRY FROM THE INVENTORY SECTION."
          );
        }
      }


      setSuccess(successText);

      resetForm();

    } catch {
      setError(
        "CANNOT REACH THE THRIFTLOOP SERVER."
      );

    } finally {
      setSaving(false);
    }
  };


  return (
    <>

      <section className="admin-header">

        <p className="section-label">
          [ ADD PRODUCT ]
        </p>


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


      <div className="admin-content">

        <div className="admin-card">

          <h2>
            NEW PRODUCT
          </h2>


          <div className="form-group">
            <label>NAME</label>

            <input
              type="text"
              placeholder="Product name"
              value={name}
              disabled={saving}
              onChange={(event) =>
                setName(event.target.value)
              }
            />
          </div>


          <div className="form-group">
            <label>CATEGORY</label>

            <input
              type="text"
              placeholder="TOPS / PRE-LOVED"
              value={category}
              disabled={saving}
              onChange={(event) =>
                setCategory(event.target.value)
              }
            />
          </div>


          <div className="form-group">
            <label>TYPE</label>

            <input
              type="text"
              placeholder="CLASSIC (optional)"
              value={type}
              disabled={saving}
              onChange={(event) =>
                setType(event.target.value)
              }
            />
          </div>


          <div className="form-group">
            <label>PRICE (₱)</label>

            <input
              type="number"
              min="1"
              step="0.01"
              placeholder="0.00"
              value={price}
              disabled={saving}
              onChange={(event) =>
                setPrice(event.target.value)
              }
            />
          </div>


          <div className="form-group">
            <label>STOCK</label>

            <input
              type="number"
              min="0"
              placeholder="1"
              value={stock}
              disabled={saving}
              onChange={(event) =>
                setStock(event.target.value)
              }
            />
          </div>


          <div className="form-group">
            <label>DESCRIPTION</label>

            <input
              type="text"
              placeholder="Short description (optional)"
              value={description}
              disabled={saving}
              onChange={(event) =>
                setDescription(event.target.value)
              }
            />
          </div>


          <div className="form-group">
            <label>IMAGE (OPTIONAL)</label>

            <input
              type="file"
              accept="image/jpeg,image/png,image/webp,image/gif"
              ref={imageInputRef}
              disabled={saving}
              onChange={(event) =>
                setImageFile(event.target.files[0] || null)
              }
            />
          </div>


          <button
            className="checkout-button"
            onClick={handleAdd}
            disabled={saving}
          >
            {saving ? "ADDING..." : "ADD PRODUCT →"}
          </button>

        </div>

      </div>

    </>
  );
}

export default AdminAddProduct;
