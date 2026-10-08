import { useEffect, useState } from "react";

import ProductCard from "../components/ProductCard";

function Products({ navigate }) {
  const [products, setProducts] = useState([
    {
      id: "001",
      name: "Classic T-Shirt",
      category: "TOPS / PRE-LOVED",
      price: 499,
      type: "CLASSIC",
    },
    {
      id: "002",
      name: "Denim Jacket",
      category: "OUTERWEAR / VINTAGE",
      price: 899,
      type: "DENIM",
    },
    {
      id: "003",
      name: "Graphic Tee",
      category: "TOPS / Y2K",
      price: 599,
      type: "GRAPHIC",
    },
    {
      id: "004",
      name: "Cargo Pants",
      category: "BOTTOMS / UTILITY",
      price: 799,
      type: "CARGO",
    },
    {
      id: "005",
      name: "Vintage Polo",
      category: "TOPS / RETRO",
      price: 649,
      type: "POLO",
    },
    {
      id: "006",
      name: "Classic Jeans",
      category: "BOTTOMS / DENIM",
      price: 899,
      type: "JEANS",
    },
  ]);


  useEffect(() => {

    let active = true;


    const loadProducts = async () => {
      try {
        const response = await fetch(
          "http://localhost/thriftloop/api/products/list.php"
        );

        const data = await response.json();


        if (!active || !data.success) return;


        setProducts(
          data.products.map((product) => ({
            id: String(product.id).padStart(3, "0"),

            name: product.name,

            category: product.category,

            price: product.price,

            stock: product.stock,

            image_url: product.image_url,

            type: product.type,
          }))
        );

      } catch {
        // KEEP THE BUILT-IN LIST IF THE API IS UNREACHABLE.
      }
    };


    loadProducts();


    return () => {
      active = false;
    };

  }, []);

  return (
    <main className="shop-page">

      <section className="shop-header">

        <div>

          <p className="section-label">
            [ THRIFTLOOP COLLECTION ]
          </p>

          <h1>
            SHOP
            <br />
            THE LOOP.
          </h1>

        </div>

        <div className="shop-description">

          <p>
            PRE-LOVED CLOTHING.
            <br />
            ONE PIECE AT A TIME.
          </p>

          <span>
            {products.length} AVAILABLE PIECES
          </span>

        </div>

      </section>

      <div className="shop-divider">

        <span>ALL ITEMS</span>
        <span>NEW ARRIVALS</span>
        <span>VINTAGE</span>
        <span>Y2K</span>

      </div>

      <section className="products-grid">

        {products.map((product) => (
          <ProductCard
            key={product.id}
            product={product}
            navigate={navigate}
          />
        ))}

      </section>

    </main>
  );
}

export default Products;