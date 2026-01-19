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
import { Link } from "react-router-dom";
import toast from "react-hot-toast";
import { useAuth } from "../contexts/AuthContext";

const RegisterPage = () => {
  const { register } = useAuth();

  const [form, setForm] = useState({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
  });

  const [showPassword, setShowPassword] = useState(false);

  const handleChange = (e) => {
    setForm({ ...form, [e.target.name]: e.target.value });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    if (form.password !== form.password_confirmation) {
      toast.error("Les mots de passe ne correspondent pas ❌");
      return;
    }

    try {
      await register(form);
      toast.success("Compte créé avec succès 🎉");
    } catch {
      toast.error("Erreur lors de l'inscription ❌");
    }
  };

  return (
    <div style={{ minHeight: "100vh", background: "#f5f5f5" }}>
      <Container fluid className="h-100 g-0">
        <Row className="h-100 g-0">

          {/* LEFT COLUMN — IMAGE NY HAVANA */}
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
              <h3 className="fw-semibold">Rejoignez votre espace sécurisé</h3>

              <ul className="fs-5 mt-3" style={{ listStyle: "none", padding: 0 }}>
                <li>✔ Sécurité</li>
                <li>✔ Fiabilité</li>
                <li>✔ Performances</li>
                <li>✔ Accessibilité</li>
              </ul>
            </div>
          </Col>

          {/* RIGHT COLUMN — REGISTER FORM */}
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
                  Créer un compte
                </h4>
              </div>

              <Form onSubmit={handleSubmit}>
                {/* Name */}
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold" style={{ color: "#8A0000" }}>
                    Nom complet
                  </Form.Label>
                  <Form.Control
                    type="text"
                    name="name"
                    placeholder="Votre nom"
                    value={form.name}
                    onChange={handleChange}
                    required
                    size="lg"
                    style={{
                      borderRadius: "10px",
                      borderColor: "#D7000F",
                    }}
                  />
                </Form.Group>

                {/* Email */}
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold" style={{ color: "#8A0000" }}>
                    Adresse email
                  </Form.Label>
                  <Form.Control
                    type="email"
                    name="email"
                    placeholder="email@exemple.com"
                    value={form.email}
                    onChange={handleChange}
                    required
                    size="lg"
                    style={{
                      borderRadius: "10px",
                      borderColor: "#D7000F",
                    }}
                  />
                </Form.Group>

                {/* Password */}
                <Form.Group className="mb-3">
                  <Form.Label className="fw-semibold" style={{ color: "#8A0000" }}>
                    Mot de passe
                  </Form.Label>
                  <InputGroup>
                    <Form.Control
                      type={showPassword ? "text" : "password"}
                      name="password"
                      placeholder="Mot de passe"
                      value={form.password}
                      onChange={handleChange}
                      required
                      size="lg"
                      style={{
                        borderRadius: "10px",
                        borderColor: "#D7000F",
                      }}
                    />
                    <Button
                      variant="outline-secondary"
                      onClick={() => setShowPassword(!showPassword)}
                    >
                      {showPassword ? "Masquer" : "Afficher"}
                    </Button>
                  </InputGroup>
                </Form.Group>

                {/* Confirm Password */}
                <Form.Group className="mb-4">
                  <Form.Label className="fw-semibold" style={{ color: "#8A0000" }}>
                    Confirmer le mot de passe
                  </Form.Label>
                  <Form.Control
                    type={showPassword ? "text" : "password"}
                    name="password_confirmation"
                    placeholder="Confirmez"
                    value={form.password_confirmation}
                    onChange={handleChange}
                    required
                    size="lg"
                    style={{
                      borderRadius: "10px",
                      borderColor: "#D7000F",
                    }}
                  />
                </Form.Group>

                {/* Submit */}
                <Button
                  type="submit"
                  className="w-100 py-2"
                  size="lg"
                  style={{
                    backgroundColor: "#D7000F",
                    borderColor: "#D7000F",
                    borderRadius: "10px",
                    fontWeight: "600",
                  }}
                >
                  S'inscrire
                </Button>

                <div className="text-center mt-3">
                  <small style={{ color: "#8A0000" }}>
                    Déjà un compte ?{" "}
                    <Link
                      to="/login"
                      className="fw-bold"
                      style={{ color: "#D7000F" }}
                    >
                      Se connecter
                    </Link>
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

export default RegisterPage;
