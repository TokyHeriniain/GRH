import React, { useState } from "react";
import api from "../../axios";
import { Card, Form, Button, Alert, Spinner } from "react-bootstrap";
import { useNavigate } from "react-router-dom";

export default function ChangePassword() {
  const navigate = useNavigate();

  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setError(null);
    setSuccess(null);

    if (password.length < 6) {
      setError("Le mot de passe doit contenir au moins 6 caractères");
      return;
    }

    if (password !== passwordConfirmation) {
      setError("Les mots de passe ne correspondent pas");
      return;
    }

    setLoading(true);

    try {
      await api.post("/api/change-password", {
        password,
        password_confirmation: passwordConfirmation,
      });

      setSuccess("Mot de passe modifié avec succès 🎉");
      setTimeout(() => {
        navigate("/dashboard"); // ou /employe/dashboard
      }, 1200);

    } catch (e) {
      setError(
        e.response?.data?.message ||
        "Erreur lors du changement de mot de passe"
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="d-flex justify-content-center align-items-center vh-100 bg-light">
      <Card style={{ width: "420px" }} className="shadow-sm border-0">
        <Card.Body>
          <h4 className="mb-3 text-center">🔐 Changement de mot de passe</h4>

          <p className="text-muted small text-center">
            Pour des raisons de sécurité, vous devez définir un nouveau mot de passe.
          </p>

          {error && <Alert variant="danger">{error}</Alert>}
          {success && <Alert variant="success">{success}</Alert>}

          <Form onSubmit={handleSubmit}>
            <Form.Group className="mb-3">
              <Form.Label>Nouveau mot de passe</Form.Label>
              <Form.Control
                type="password"
                value={password}
                onChange={(e) => setPassword(e.target.value)}
                placeholder="••••••••"
                required
              />
            </Form.Group>

            <Form.Group className="mb-3">
              <Form.Label>Confirmation</Form.Label>
              <Form.Control
                type="password"
                value={passwordConfirmation}
                onChange={(e) => setPasswordConfirmation(e.target.value)}
                placeholder="••••••••"
                required
              />
            </Form.Group>

            <Button
              type="submit"
              variant="success"
              className="w-100"
              disabled={loading}
            >
              {loading ? (
                <>
                  <Spinner size="sm" animation="border" /> Enregistrement...
                </>
              ) : (
                "Changer le mot de passe"
              )}
            </Button>
          </Form>
        </Card.Body>
      </Card>
    </div>
  );
}
