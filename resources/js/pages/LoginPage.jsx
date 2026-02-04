import { useState } from "react";
import {
  Container,
  Row,
  Col,
  Card,
  Form,
  Button,
  InputGroup,
} from "react-bootstrap";
import { useNavigate } from "react-router-dom";
import { useAuth } from "../contexts/AuthContext";
import toast from "react-hot-toast";

const LoginPage = () => {
  const { login } = useAuth();
  const navigate = useNavigate();

  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);

    try {
      const response = await login(email, password);

      // 👉 Si première connexion → forcer changement mot de passe
      if (response?.must_change_password) {
        toast("Veuillez changer votre mot de passe 🔐", { icon: "⚠️" });
        navigate("/change-password");
        return;
      }

      toast.success("Connexion réussie 🎉");
      navigate("/dashboard"); // ou employe/dashboard

    } catch (error) {
      toast.error("Identifiants incorrects ❌");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ minHeight: "100vh", background: "#f5f5f5" }}>
      <Container fluid className="h-100 g-0">
        <Row className="h-100 g-0">

          {/* LEFT COLUMN */}
          <Col
            md={6}
            className="d-none d-md-block position-relative"
            style={{
              backgroundImage: 'url("/images/login-bg-nyhavana.jpg")',
              backgroundSize: "cover",
              backgroundPosition: "center",
              minHeight: "100vh",
            }}
          >
            <div
              style={{
                position: "absolute",
                inset: 0,
                background:
                  "linear-gradient(90deg, rgba(137,0,0,0.75), rgba(215,0,15,0.65))",
              }}
            />

            <div
              className="position-absolute text-white px-5"
              style={{ bottom: "50px" }}
            >
              <h1 className="fw-bold display-4">NY HAVANA</h1>
              <h3 className="fw-semibold">Ses engagements :</h3>

              <ul className="fs-5 mt-3" style={{ listStyle: "none", padding: 0 }}>
                <li>✔ La proximité</li>
                <li>✔ La qualité</li>
                <li>✔ Le dynamisme</li>
                <li>✔ L’innovation</li>
              </ul>
            </div>
          </Col>

          {/* RIGHT COLUMN */}
          <Col
            md={6}
            className="d-flex justify-content-center align-items-center px-4"
          >
            <Card
              className="shadow p-4 border-0"
              style={{
                width: "100%",
                maxWidth: "420px",
                borderRadius: "16px",
              }}
            >
              <div className="text-center mb-4">
                <img
                  src="/images/ny-havana-logo.png"
                  alt="Ny Havana Logo"
                  style={{ width: "130px", marginBottom: "10px" }}
                />
                <h4 className="fw-bold" style={{ color: "#B10000" }}>
                  G.R.H : Gestion des Ressources Humaines
                </h4>
              </div>

              <Form onSubmit={handleSubmit}>
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold" style={{ color: "#8A0000" }}>
                    Adresse email
                  </Form.Label>
                  <Form.Control
                    type="email"
                    placeholder="email@exemple.com"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    required
                    size="lg"
                    disabled={loading}
                    style={{
                      borderRadius: "10px",
                      borderColor: "#D7000F",
                    }}
                  />
                </Form.Group>

                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold" style={{ color: "#8A0000" }}>
                    Mot de passe
                  </Form.Label>
                  <InputGroup>
                    <Form.Control
                      type={showPassword ? "text" : "password"}
                      placeholder="Votre mot de passe"
                      value={password}
                      onChange={(e) => setPassword(e.target.value)}
                      required
                      size="lg"
                      disabled={loading}
                      style={{
                        borderRadius: "10px",
                        borderColor: "#D7000F",
                      }}
                    />
                    <Button
                      variant="outline-secondary"
                      onClick={() => setShowPassword(!showPassword)}
                      disabled={loading}
                    >
                      {showPassword ? "Masquer" : "Afficher"}
                    </Button>
                  </InputGroup>
                </Form.Group>

                <Button
                  type="submit"
                  className="w-100 py-2 mt-2"
                  size="lg"
                  disabled={loading}
                  style={{
                    backgroundColor: "#D7000F",
                    borderColor: "#D7000F",
                    borderRadius: "10px",
                    fontWeight: "600",
                  }}
                >
                  {loading ? "Connexion..." : "Se connecter"}
                </Button>

                <div className="text-center mt-3">
                  <small style={{ color: "#8A0000" }}>
                    Pas encore inscrit ? Veuillez contacter l'administrateur.
                  </small>
                </div>
              </Form>
            </Card>
          </Col>
        </Row>
      </Container>
    </div>
  );
};

export default LoginPage;
