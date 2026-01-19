import { FiMenu } from "react-icons/fi";
import { Image } from "react-bootstrap";
import { useNavigation } from "./NavigationContext";

const Topbar = () => {
  const { mobileOpen, setMobileOpen, toggleTheme, theme } = useNavigation();

  return (
    <>
      <header
        className="d-flex justify-content-between align-items-center px-3"
        style={{
          height: "65px",
          background: "#161616",
          borderBottom: "2px solid #B30000",
          position: "fixed",
          top: 0,
          left: 0,
          right: 0,
          zIndex: 1200,
        }}
      >
        {/* LOGO */}
        <div className="d-flex align-items-center gap-2">
          <button
            className="btn btn-outline-light d-md-none"
            onClick={() => setMobileOpen(true)}
          >
            <FiMenu size={22} />
          </button>

          <Image
            src="/images/ny-havana-logo.png"
            roundedCircle
            style={{ width: "45px", height: "45px", objectFit: "cover" }}
          />
          <h5 className="text-white fw-bold m-0">Ny Havana</h5>
        </div>

        <button className="btn btn-outline-light" onClick={toggleTheme}>
          {theme === "light" ? "🌙" : "☀️"}
        </button>
      </header>

      <div style={{ height: "65px" }}></div>
    </>
  );
};

export default Topbar;
