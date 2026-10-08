function Home({ navigate }) {
  return (
    <main>

      {/* =========================
          HERO
      ========================= */}

      <section className="hero">

        <div className="hero-text">

          <p className="eyebrow">
            EST. 2026 • THRIFTLOOP
          </p>

          <h1>
            WEAR IT.
            <br />
            LOVE IT.
            <br />
            LOOP IT.
          </h1>

          <p className="hero-description">
            Curated pre-loved pieces for people
            who believe good style never gets old.
          </p>

          <button
            className="shop-button"
            onClick={() => navigate("products")}
          >
            EXPLORE THE COLLECTION →
          </button>

        </div>


        <div className="hero-card">

          <div className="stamp">
            PRE-LOVED
          </div>

          <div className="clothing-placeholder">
            THRIFT
            <br />
            FINDS
          </div>

          <p>
            ONE PIECE.
            <br />
            ONE STORY.
          </p>

        </div>

      </section>


      {/* =========================
          MARQUEE
      ========================= */}

      <section className="marquee">

        <span>
          THRIFT • VINTAGE • PRE-LOVED • RE-WEAR • THRIFT • VINTAGE • PRE-LOVED •
        </span>

      </section>


      {/* =========================
          INTRO
      ========================= */}

      <section className="intro">

        <p className="section-label">
          [ ABOUT THRIFTLOOP ]
        </p>

        <h2>
          CLOTHES WITH
          <br />
          A SECOND LIFE.
        </h2>

        <p className="intro-text">
          ThriftLoop is a digital thrift shop made for
          people who want unique pieces without
          buying into fast fashion.
        </p>

      </section>

    </main>
  );
}

export default Home;