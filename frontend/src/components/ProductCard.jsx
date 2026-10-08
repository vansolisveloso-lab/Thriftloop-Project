import { useState } from "react";
import { useCart } from "./CartContext";

function ProductCard({ product, navigate }) {
  const { addToCart } = useCart();

  const [added, setAdded] = useState(false);

  // SOLD OUT WHEN STOCK HITS ZERO — BUTTON STAYS INERT.
  const soldOut =
    typeof product.stock === "number" &&
    product.stock <= 0;

  const handleAddToCart = () => {

    if (soldOut) {
      return;
    }

    // FIRES THE "ADDED TO BAG" TOAST — STAYS ON THIS PAGE,
    // NO REDIRECT TO THE CART.
    addToCart(product);

    setAdded(true);

    setTimeout(() => {
      setAdded(false);
    }, 1500);
  };

  return (
    <div className="product-card">

      <div className="product-image">

        {product.image_url && (
          <img
            className="product-photo"
            src={product.image_url}
            alt={product.name}
          />
        )}

        <span className="product-number">
          #{product.id}
        </span>

        {!product.image_url && (
          <div className="product-image-text">
            {product.type}
          </div>
        )}

      </div>

      <div className="product-info">

        <div>

          <p className="product-category">
            {product.category}
          </p>

          <h3>
            {product.name}
          </h3>

        </div>

        <p className="product-price">
          ₱{product.price.toLocaleString()}
        </p>

      </div>

      <button
        className="add-button"
        onClick={handleAddToCart}
        disabled={soldOut}
      >
        {soldOut
          ? "SOLD OUT"
          : added
            ? "ADDED ✓"
            : "ADD TO BAG"}
      </button>

    </div>
  );
}

export default ProductCard;
