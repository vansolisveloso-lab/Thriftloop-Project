import { useState } from "react";

function AIStylist({ product }) {
  const [recommendation, setRecommendation] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const getStyleRecommendation = async () => {
    setLoading(true);
    setError("");
    setRecommendation("");

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/stylist/recommend.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            product_name: product.name,
            category: product.category,
            description: product.description,
          }),
        }
      );

      const data = await response.json();

      console.log("AI SERVER RESPONSE:", data);

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || "FAILED TO GET STYLE RECOMMENDATION."
        );
      }

      setRecommendation(data.recommendation);
    } catch (error) {
      console.error("AI STYLIST ERROR:", error);
      setError(error.message || "FAILED TO CONNECT TO AI STYLIST.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="ai-stylist">
      <button
        type="button"
        onClick={getStyleRecommendation}
        disabled={loading}
      >
        {loading ? "✨ STYLING..." : "✨ AI STYLE ME"}
      </button>

      {error && (
        <p className="ai-stylist-error">
          {error}
        </p>
      )}

      {recommendation && (
        <div className="ai-stylist-result">
          <h3>✨ AI FASHION STYLIST</h3>
          <p style={{ whiteSpace: "pre-line" }}>
            {recommendation}
          </p>
        </div>
      )}
    </div>
  );
}

export default AIStylist;