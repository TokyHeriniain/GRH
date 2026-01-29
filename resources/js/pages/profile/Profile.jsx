import React, { useEffect, useState } from "react";
import api from "axios";
import {
  Card,
  Form,
  Button,
  Row,
  Col,
  Alert,
  Spinner,
} from "react-bootstrap";
import { toast } from "react-toastify";
import NavigationLayout from "../../components/NavigationLayout";

export default function Profile() {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);

  const [form, setForm] = useState({
    name: "",
    email: "",
  });

  const [passwordForm, setPasswordForm] = useState({
    current_password: "",
    password: "",
    password_confirmation: "",
  });

  useEffect(() => {
    fetchProfile();
  }, []);

  const fetchProfile = async () => {
    try {
      const res = await api.get("/api/profile");
      setUser(res.data);
      setForm({
        name: res.data.name,
        email: res.data.email,
      });
    } catch {
      toast.error("Erreur chargement profil");
    } finally {
      setLoading(false);
    }
  };

  const updateProfile = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      await api.put("/api/profile", form);
      toast.success("Profil mis à jour");
      fetchProfile();
    } catch (e) {
      toast.error(e.response?.data?.message || "Erreur mise à jour");
    } finally {
      setSaving(false);
    }
  };

  const updatePassword = async (e) => {
    e.preventDefault();
    try {
      await api.put("/api/profile/password", passwordForm);
      toast.success("Mot de passe modifié");
      setPasswordForm({
        current_password: "",
        password: "",
        password_confirmation: "",
      });
    } catch (e) {
      toast.error(e.response?.data?.error || "Erreur mot de passe");
    }
  };

  if (loading) {
    return (
      <NavigationLayout>
        <div className="text-center py-5">
          <Spinner animation="border" />
        </div>
      </NavigationLayout>
    );
  }

  return (
    <NavigationLayout>
      <Card className="shadow-sm border-0 p-4">
        <h4 className="mb-3">👤 Mon profil</h4>

        <Alert variant="info">
          Rôle : <strong>{user.role?.name}</strong>
        </Alert>

        {/* INFOS */}
        <Form onSubmit={updateProfile}>
          <Row className="g-3">
            <Col md={6}>
              <Form.Label>Nom</Form.Label>
              <Form.Control
                value={form.name}
                onChange={(e) =>
                  setForm({ ...form, name: e.target.value })
                }
              />
            </Col>

            <Col md={6}>
              <Form.Label>Email</Form.Label>
              <Form.Control
                type="email"
                value={form.email}
                onChange={(e) =>
                  setForm({ ...form, email: e.target.value })
                }
              />
            </Col>

            <Col md={12}>
              <Button type="submit" disabled={saving}>
                💾 Enregistrer
              </Button>
            </Col>
          </Row>
        </Form>

        <hr />

        {/* MOT DE PASSE */}
        <h5>🔒 Changer le mot de passe</h5>

        <Form onSubmit={updatePassword}>
          <Row className="g-3">
            <Col md={4}>
              <Form.Control
                type="password"
                placeholder="Mot de passe actuel"
                value={passwordForm.current_password}
                onChange={(e) =>
                  setPasswordForm({
                    ...passwordForm,
                    current_password: e.target.value,
                  })
                }
              />
            </Col>

            <Col md={4}>
              <Form.Control
                type="password"
                placeholder="Nouveau mot de passe"
                value={passwordForm.password}
                onChange={(e) =>
                  setPasswordForm({
                    ...passwordForm,
                    password: e.target.value,
                  })
                }
              />
            </Col>

            <Col md={4}>
              <Form.Control
                type="password"
                placeholder="Confirmation"
                value={passwordForm.password_confirmation}
                onChange={(e) =>
                  setPasswordForm({
                    ...passwordForm,
                    password_confirmation: e.target.value,
                  })
                }
              />
            </Col>

            <Col md={12}>
              <Button variant="outline-primary" type="submit">
                🔑 Modifier le mot de passe
              </Button>
            </Col>
          </Row>
        </Form>
      </Card>
    </NavigationLayout>
  );
}
