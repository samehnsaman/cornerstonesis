import { Head, Link, router, useForm, usePage } from "@inertiajs/react";
import React, { FormEvent, PropsWithChildren } from "react";

type Shared = {
    locale: "en" | "ar";
    flash: {
        success?: string;
        temporary_password?: string;
        temporary_recovery_codes?: string[];
    };
};
const tr = (locale: string, en: string, ar: string) =>
    locale === "ar" ? ar : en;
const input =
    "mt-1 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-slate-950 focus:border-indigo-600 focus:outline-none";
const btn =
    "rounded-lg bg-indigo-700 px-4 py-2 font-bold text-white disabled:opacity-50";
function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <label className="block text-sm font-semibold text-slate-700">
            {label}
            {children}
        </label>
    );
}
function Text(p: React.InputHTMLAttributes<HTMLInputElement>) {
    return <input {...p} className={input} />;
}
function Select(p: React.SelectHTMLAttributes<HTMLSelectElement>) {
    return <select {...p} className={input} />;
}
function Card({
    title,
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 className="mb-4 text-lg font-black text-slate-900">{title}</h2>
            {children}
        </section>
    );
}
function Errors({ errors }: { errors: Record<string, string> }) {
    return (
        <>
            {Object.entries(errors).map(([k, v]) => (
                <p role="alert" className="mt-2 text-sm text-red-700" key={k}>
                    {v}
                </p>
            ))}
        </>
    );
}
function Submit({ busy, label }: { busy: boolean; label: string }) {
    return (
        <button disabled={busy} className={btn}>
            {label}
        </button>
    );
}

const nav = [
    ["/admin", "Dashboard", "لوحة التحكم"],
    ["/admin/college", "College Setup", "إعداد الكلية"],
    ["/admin/catalog", "Academic Catalog", "الدليل الأكاديمي"],
    ["/admin/academics", "Terms & Sections", "الفصول والشعب"],
    ["/admin/people", "People & Access", "الأشخاص والصلاحيات"],
    ["/admin/admissions", "Admissions", "القبول"],
    ["/admin/matriculation", "Matriculation", "التحويل إلى طالب"],
    ["/admin/audit", "Audit Log", "سجل التدقيق"],
];
function AdminShell({
    module,
    children,
}: PropsWithChildren<{ module: string }>) {
    const { locale, flash } = usePage<Shared>().props;
    return (
        <div
            dir={locale === "ar" ? "rtl" : "ltr"}
            className="min-h-screen bg-slate-100 text-slate-900"
        >
            <div className="bg-amber-300 px-4 py-2 text-center text-xs font-black tracking-widest">
                DEMO — NOT OFFICIAL · SYNTHETIC DATA ONLY
            </div>
            <div className="lg:grid lg:grid-cols-[17rem_1fr]">
                <aside className="bg-indigo-950 p-5 text-white lg:min-h-[calc(100vh-2rem)]">
                    <Link href="/admin" className="text-xl font-black">
                        Cornerstone <span className="text-teal-400">SIS</span>
                    </Link>
                    <div className="mt-1 text-xs text-indigo-200">
                        {tr(locale, "Administration", "الإدارة")}
                    </div>
                    <nav className="mt-8 grid gap-1">
                        {nav.map(([href, en, ar]) => (
                            <Link
                                key={href}
                                href={href}
                                className={`rounded-lg px-3 py-2 text-sm ${location.pathname === href ? "bg-white text-indigo-950" : "hover:bg-indigo-900"}`}
                            >
                                {tr(locale, en, ar)}
                            </Link>
                        ))}
                    </nav>
                    <div className="mt-8 flex gap-2 text-sm">
                        <button
                            onClick={() =>
                                router.post("/locale", {
                                    locale: locale === "en" ? "ar" : "en",
                                })
                            }
                            className="rounded border border-indigo-500 px-3 py-2"
                        >
                            {locale === "en" ? "العربية" : "English"}
                        </button>
                        <Link
                            href="/dashboard"
                            className="rounded border border-indigo-500 px-3 py-2"
                        >
                            {tr(locale, "Portal", "البوابة")}
                        </Link>
                    </div>
                </aside>
                <main className="min-w-0 p-4 md:p-8">
                    <Head title={tr(locale, "Administration", "الإدارة")} />
                    {flash.success && (
                        <div
                            role="status"
                            className="mb-4 rounded-lg bg-emerald-100 p-3 text-emerald-900"
                        >
                            {flash.success}
                        </div>
                    )}
                    {flash.temporary_password && (
                        <div
                            role="alert"
                            className="mb-4 rounded-lg border-2 border-amber-500 bg-amber-50 p-4"
                        >
                            <b>
                                {tr(
                                    locale,
                                    "Copy this one-time temporary password now:",
                                    "انسخ كلمة المرور المؤقتة الآن:",
                                )}
                            </b>{" "}
                            <code className="select-all text-lg">
                                {flash.temporary_password}
                            </code>
                            {flash.temporary_recovery_codes && (
                                <div className="mt-3">
                                    <b>
                                        {tr(
                                            locale,
                                            "One-time recovery codes:",
                                            "رموز الاسترداد للاستخدام مرة واحدة:",
                                        )}
                                    </b>
                                    <div className="mt-1 grid grid-cols-2 gap-1 font-mono">
                                        {flash.temporary_recovery_codes.map(
                                            (x) => (
                                                <code key={x}>{x}</code>
                                            ),
                                        )}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                    <header className="mb-6">
                        <h1 className="text-3xl font-black capitalize">
                            {tr(
                                locale,
                                module.replaceAll("_", " "),
                                (
                                    {
                                        dashboard: "لوحة التحكم",
                                        college: "إعداد الكلية",
                                        catalog: "الدليل الأكاديمي",
                                        academics: "الفصول والشعب",
                                        people: "الأشخاص والصلاحيات",
                                        admissions: "القبول",
                                        matriculation: "التحويل إلى طالب",
                                        audit: "سجل التدقيق",
                                    } as any
                                )[module] || module,
                            )}
                        </h1>
                    </header>
                    {children}
                </main>
            </div>
        </div>
    );
}

function Dashboard(p: any) {
    const { locale } = usePage<Shared>().props;
    return (
        <AdminShell module="dashboard">
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                {Object.entries(p.metrics || {}).map(([k, v]) => (
                    <Card
                        key={k}
                        title={tr(locale, k.replace(/([A-Z])/g, " $1"), k)}
                    >
                        <div className="text-3xl font-black text-indigo-800">
                            {String(v)}
                        </div>
                    </Card>
                ))}
            </div>
            <Card title={tr(locale, "Setup completeness", "اكتمال الإعداد")}>
                <div className="grid gap-2 sm:grid-cols-2">
                    {Object.entries(p.setup || {}).map(([k, v]) => (
                        <div
                            key={k}
                            className={`rounded p-3 ${v ? "bg-emerald-100" : "bg-amber-100"}`}
                        >
                            {v ? "✓" : "○"} {k.replaceAll("_", " ")}
                        </div>
                    ))}
                </div>
            </Card>
        </AdminShell>
    );
}

function College(p: any) {
    const { locale } = usePage<Shared>().props;
    const org = useForm({
        code: p.organization?.code || "",
        name_en: p.organization?.name_en || "",
        name_ar: p.organization?.name_ar || "",
        legal_name_en: p.organization?.legal_name_en || "",
        legal_name_ar: p.organization?.legal_name_ar || "",
        timezone: p.organization?.timezone || "UTC",
        default_locale: p.organization?.default_locale || "en",
        default_currency: p.organization?.default_currency || "USD",
        supported_currencies: p.organization?.supported_currencies || ["USD"],
        email: p.organization?.email || "",
        phone: p.organization?.phone || "",
        address_en: p.organization?.address_en || "",
        address_ar: p.organization?.address_ar || "",
    });
    const campus = useForm({
        code: "",
        name_en: "",
        name_ar: "",
        timezone: p.organization?.timezone || "UTC",
    });
    const dept = useForm({ campus_id: "", code: "", name_en: "", name_ar: "" });
    const room = useForm({
        campus_id: "",
        code: "",
        name_en: "",
        name_ar: "",
        capacity: 30,
    });
    return (
        <AdminShell module="college">
            <div className="grid gap-5 xl:grid-cols-2">
                <Card title={tr(locale, "College identity", "هوية الكلية")}>
                    <form
                        className="grid gap-3 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            org.put("/admin/college");
                        }}
                    >
                        <Field label="Code">
                            <Text
                                value={org.data.code}
                                onChange={(e) =>
                                    org.setData("code", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="Timezone">
                            <Text
                                value={org.data.timezone}
                                onChange={(e) =>
                                    org.setData("timezone", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="English name">
                            <Text
                                value={org.data.name_en}
                                onChange={(e) =>
                                    org.setData("name_en", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="الاسم العربي">
                            <Text
                                dir="rtl"
                                value={org.data.name_ar}
                                onChange={(e) =>
                                    org.setData("name_ar", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="Legal name">
                            <Text
                                value={org.data.legal_name_en}
                                onChange={(e) =>
                                    org.setData("legal_name_en", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="الاسم القانوني">
                            <Text
                                dir="rtl"
                                value={org.data.legal_name_ar}
                                onChange={(e) =>
                                    org.setData("legal_name_ar", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="Currency">
                            <Text
                                maxLength={3}
                                value={org.data.default_currency}
                                onChange={(e) => {
                                    org.setData(
                                        "default_currency",
                                        e.target.value.toUpperCase(),
                                    );
                                    org.setData("supported_currencies", [
                                        e.target.value.toUpperCase(),
                                    ]);
                                }}
                            />
                        </Field>
                        <Field label="Email">
                            <Text
                                type="email"
                                value={org.data.email}
                                onChange={(e) =>
                                    org.setData("email", e.target.value)
                                }
                            />
                        </Field>
                        <Field label="Phone">
                            <Text
                                value={org.data.phone}
                                onChange={(e) =>
                                    org.setData("phone", e.target.value)
                                }
                            />
                        </Field>
                        <div className="self-end">
                            <Submit
                                busy={org.processing}
                                label={tr(locale, "Save college", "حفظ الكلية")}
                            />
                        </div>
                        <Errors errors={org.errors} />
                    </form>
                </Card>
                <Card title={tr(locale, "Add campus", "إضافة حرم")}>
                    <form
                        className="grid gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            campus.post("/admin/campuses", {
                                onSuccess: () =>
                                    campus.reset("code", "name_en", "name_ar"),
                            });
                        }}
                    >
                        <Text
                            placeholder="Code"
                            value={campus.data.code}
                            onChange={(e) =>
                                campus.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English name"
                            value={campus.data.name_en}
                            onChange={(e) =>
                                campus.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            dir="rtl"
                            placeholder="الاسم العربي"
                            value={campus.data.name_ar}
                            onChange={(e) =>
                                campus.setData("name_ar", e.target.value)
                            }
                        />
                        <Text
                            placeholder="Timezone"
                            value={campus.data.timezone}
                            onChange={(e) =>
                                campus.setData("timezone", e.target.value)
                            }
                        />
                        <Submit
                            busy={campus.processing}
                            label={tr(locale, "Add campus", "إضافة")}
                        />
                        <Errors errors={campus.errors} />
                    </form>
                </Card>
                <Card title={tr(locale, "Add department", "إضافة قسم")}>
                    <form
                        className="grid gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            dept.post("/admin/departments", {
                                onSuccess: () =>
                                    dept.reset("code", "name_en", "name_ar"),
                            });
                        }}
                    >
                        <Select
                            value={dept.data.campus_id}
                            onChange={(e) =>
                                dept.setData("campus_id", e.target.value)
                            }
                        >
                            <option value="">College-wide</option>
                            {p.campuses.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code} · {x.name_en}
                                </option>
                            ))}
                        </Select>
                        <Text
                            placeholder="Code"
                            value={dept.data.code}
                            onChange={(e) =>
                                dept.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English name"
                            value={dept.data.name_en}
                            onChange={(e) =>
                                dept.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            dir="rtl"
                            placeholder="الاسم العربي"
                            value={dept.data.name_ar}
                            onChange={(e) =>
                                dept.setData("name_ar", e.target.value)
                            }
                        />
                        <Submit
                            busy={dept.processing}
                            label={tr(locale, "Add department", "إضافة")}
                        />
                        <Errors errors={dept.errors} />
                    </form>
                </Card>
                <Card title={tr(locale, "Add room", "إضافة قاعة")}>
                    <form
                        className="grid gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            room.post("/admin/rooms", {
                                onSuccess: () =>
                                    room.reset("code", "name_en", "name_ar"),
                            });
                        }}
                    >
                        <Select
                            value={room.data.campus_id}
                            onChange={(e) =>
                                room.setData("campus_id", e.target.value)
                            }
                        >
                            <option value="">Select campus</option>
                            {p.campuses.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Text
                            placeholder="Code"
                            value={room.data.code}
                            onChange={(e) =>
                                room.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English name"
                            value={room.data.name_en}
                            onChange={(e) =>
                                room.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            type="number"
                            min="1"
                            value={room.data.capacity}
                            onChange={(e) =>
                                room.setData("capacity", +e.target.value)
                            }
                        />
                        <Submit
                            busy={room.processing}
                            label={tr(locale, "Add room", "إضافة")}
                        />
                        <Errors errors={room.errors} />
                    </form>
                </Card>
            </div>
            <Card title={tr(locale, "Current structure", "الهيكل الحالي")}>
                <div className="grid gap-4 md:grid-cols-3">
                    <div>
                        <b>Campuses</b>
                        {p.campuses.map((x: any) => (
                            <p key={x.id}>
                                {x.code} ·{" "}
                                {locale === "ar"
                                    ? x.name_ar || x.name_en
                                    : x.name_en}
                            </p>
                        ))}
                    </div>
                    <div>
                        <b>Departments</b>
                        {p.departments.map((x: any) => (
                            <p key={x.id}>
                                {x.code} ·{" "}
                                {locale === "ar"
                                    ? x.name_ar || x.name_en
                                    : x.name_en}
                            </p>
                        ))}
                    </div>
                    <div>
                        <b>Rooms</b>
                        {p.rooms.map((x: any) => (
                            <p key={x.id}>
                                {x.code} · {x.capacity}
                            </p>
                        ))}
                    </div>
                </div>
            </Card>
        </AdminShell>
    );
}

function Catalog(p: any) {
    const { locale } = usePage<Shared>().props;
    const program = useForm({
        department_id: "",
        code: "",
        name_en: "",
        name_ar: "",
        description_en: "",
        description_ar: "",
        award_type: "bachelor",
        required_credits: 120,
        duration_terms: 8,
    });
    const course = useForm({
        department_id: "",
        code: "",
        title_en: "",
        title_ar: "",
        version: new Date().getFullYear().toString(),
        effective_from: new Date().toISOString().slice(0, 10),
        credit_hours: 3,
        lecture_hours: 3,
        lab_hours: 0,
        grading_basis: "letter",
        description_en: "",
        description_ar: "",
    });
    return (
        <AdminShell module="catalog">
            <div className="grid gap-5 xl:grid-cols-2">
                <Card title={tr(locale, "Create program", "إنشاء برنامج")}>
                    <form
                        className="grid gap-3 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            program.post("/admin/programs");
                        }}
                    >
                        <Select
                            value={program.data.department_id}
                            onChange={(e) =>
                                program.setData("department_id", e.target.value)
                            }
                        >
                            <option value="">Department</option>
                            {p.departments.map((x: any) => (
                                <option value={x.id} key={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Text
                            placeholder="Program code"
                            value={program.data.code}
                            onChange={(e) =>
                                program.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English name"
                            value={program.data.name_en}
                            onChange={(e) =>
                                program.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            dir="rtl"
                            placeholder="الاسم العربي"
                            value={program.data.name_ar}
                            onChange={(e) =>
                                program.setData("name_ar", e.target.value)
                            }
                        />
                        <Select
                            value={program.data.award_type}
                            onChange={(e) =>
                                program.setData("award_type", e.target.value)
                            }
                        >
                            {[
                                "certificate",
                                "diploma",
                                "associate",
                                "bachelor",
                                "master",
                                "doctorate",
                            ].map((x) => (
                                <option key={x}>{x}</option>
                            ))}
                        </Select>
                        <Text
                            type="number"
                            value={program.data.required_credits}
                            onChange={(e) =>
                                program.setData(
                                    "required_credits",
                                    +e.target.value,
                                )
                            }
                        />
                        <Submit
                            busy={program.processing}
                            label={tr(locale, "Create program", "إنشاء")}
                        />
                        <Errors errors={program.errors} />
                    </form>
                </Card>
                <Card
                    title={tr(
                        locale,
                        "Create course and version",
                        "إنشاء مقرر وإصدار",
                    )}
                >
                    <form
                        className="grid gap-3 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            course.post("/admin/courses");
                        }}
                    >
                        <Select
                            value={course.data.department_id}
                            onChange={(e) =>
                                course.setData("department_id", e.target.value)
                            }
                        >
                            <option value="">Department</option>
                            {p.departments.map((x: any) => (
                                <option value={x.id} key={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Text
                            placeholder="Course code"
                            value={course.data.code}
                            onChange={(e) =>
                                course.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English title"
                            value={course.data.title_en}
                            onChange={(e) =>
                                course.setData("title_en", e.target.value)
                            }
                        />
                        <Text
                            dir="rtl"
                            placeholder="العنوان العربي"
                            value={course.data.title_ar}
                            onChange={(e) =>
                                course.setData("title_ar", e.target.value)
                            }
                        />
                        <Text
                            placeholder="Version"
                            value={course.data.version}
                            onChange={(e) =>
                                course.setData("version", e.target.value)
                            }
                        />
                        <Text
                            type="date"
                            value={course.data.effective_from}
                            onChange={(e) =>
                                course.setData("effective_from", e.target.value)
                            }
                        />
                        <Text
                            type="number"
                            step=".5"
                            value={course.data.credit_hours}
                            onChange={(e) =>
                                course.setData("credit_hours", +e.target.value)
                            }
                        />
                        <Select
                            value={course.data.grading_basis}
                            onChange={(e) =>
                                course.setData("grading_basis", e.target.value)
                            }
                        >
                            <option value="letter">Letter</option>
                            <option value="pass_fail">Pass/fail</option>
                            <option value="audit">Audit</option>
                        </Select>
                        <Submit
                            busy={course.processing}
                            label={tr(locale, "Create course", "إنشاء")}
                        />
                        <Errors errors={course.errors} />
                    </form>
                </Card>
            </div>
            <CurriculumBuilder
                locale={locale}
                programs={p.programs}
                courses={p.courses}
            />
            <div className="mt-5 grid gap-5 xl:grid-cols-2">
                <Card
                    title={tr(
                        locale,
                        "Programs and curricula",
                        "البرامج والمناهج",
                    )}
                >
                    {p.programs.map((x: any) => (
                        <div className="mb-3 rounded border p-3" key={x.id}>
                            <b>
                                {x.code} ·{" "}
                                {locale === "ar"
                                    ? x.name_ar || x.name_en
                                    : x.name_en}
                            </b>
                            <span className="float-end rounded bg-slate-100 px-2 text-xs">
                                {x.status}
                            </span>
                            {x.curriculum_versions?.map((v: any) => (
                                <div key={v.id} className="mt-2 text-sm">
                                    Curriculum {v.version} · {v.status}{" "}
                                    {v.status === "draft" && (
                                        <button
                                            className="ms-2 text-indigo-700"
                                            onClick={() =>
                                                router.post(
                                                    `/admin/publish/curriculum/${v.id}`,
                                                    {
                                                        reason: "Approved for publication",
                                                    },
                                                )
                                            }
                                        >
                                            Publish
                                        </button>
                                    )}
                                </div>
                            ))}
                        </div>
                    ))}
                </Card>
                <Card title={tr(locale, "Courses", "المقررات")}>
                    {p.courses.map((x: any) => (
                        <div className="mb-3 rounded border p-3" key={x.id}>
                            <b>
                                {x.code} ·{" "}
                                {locale === "ar"
                                    ? x.title_ar || x.title_en
                                    : x.title_en}
                            </b>
                            {x.versions?.map((v: any) => (
                                <div key={v.id} className="mt-2 text-sm">
                                    {v.version} · {v.credit_hours} credits ·{" "}
                                    {v.status}{" "}
                                    {v.status === "draft" && (
                                        <button
                                            className="ms-2 text-indigo-700"
                                            onClick={() =>
                                                router.post(
                                                    `/admin/publish/course-version/${v.id}`,
                                                    {
                                                        reason: "Approved for publication",
                                                    },
                                                )
                                            }
                                        >
                                            Publish
                                        </button>
                                    )}
                                </div>
                            ))}
                            <Coordinator course={x} faculty={p.faculty} />
                        </div>
                    ))}
                </Card>
            </div>
        </AdminShell>
    );
}

function CurriculumBuilder({
    locale,
    programs,
    courses,
}: {
    locale: string;
    programs: any[];
    courses: any[];
}) {
    const versions = courses.flatMap((c) =>
        c.versions.map((v: any) => ({ ...v, course: c })),
    );
    const f = useForm({
        program_id: "",
        version: new Date().getFullYear().toString(),
        effective_from: new Date().toISOString().slice(0, 10),
        effective_to: "",
        minimum_gpa: 2,
        groups: [
            {
                type: "required",
                name_en: "Core requirements",
                name_ar: "المتطلبات الأساسية",
                minimum_credits: 0,
                minimum_courses: 0,
                course_version_ids: [] as string[],
            },
        ],
    });
    return (
        <div className="mt-5">
            <Card
                title={tr(
                    locale,
                    "Build curriculum version",
                    "بناء إصدار المنهج",
                )}
            >
                <form
                    className="grid gap-3 md:grid-cols-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        f.post("/admin/curricula");
                    }}
                >
                    <Select
                        value={f.data.program_id}
                        onChange={(e) =>
                            f.setData("program_id", e.target.value)
                        }
                    >
                        <option value="">Program</option>
                        {programs.map((x) => (
                            <option key={x.id} value={x.id}>
                                {x.code}
                            </option>
                        ))}
                    </Select>
                    <Text
                        placeholder="Version"
                        value={f.data.version}
                        onChange={(e) => f.setData("version", e.target.value)}
                    />
                    <Text
                        type="date"
                        value={f.data.effective_from}
                        onChange={(e) =>
                            f.setData("effective_from", e.target.value)
                        }
                    />
                    <Select
                        value={f.data.groups[0].type}
                        onChange={(e) =>
                            f.setData("groups", [
                                { ...f.data.groups[0], type: e.target.value },
                            ])
                        }
                    >
                        {[
                            "required",
                            "general_education",
                            "concentration",
                            "elective",
                        ].map((x) => (
                            <option key={x}>{x}</option>
                        ))}
                    </Select>
                    <Text
                        placeholder="Requirement group"
                        value={f.data.groups[0].name_en}
                        onChange={(e) =>
                            f.setData("groups", [
                                {
                                    ...f.data.groups[0],
                                    name_en: e.target.value,
                                },
                            ])
                        }
                    />
                    <Select
                        multiple
                        value={f.data.groups[0].course_version_ids}
                        onChange={(e) =>
                            f.setData("groups", [
                                {
                                    ...f.data.groups[0],
                                    course_version_ids: Array.from(
                                        e.target.selectedOptions,
                                    ).map((o) => o.value),
                                },
                            ])
                        }
                    >
                        {versions.map((x: any) => (
                            <option key={x.id} value={x.id}>
                                {x.course.code} · {x.version}
                            </option>
                        ))}
                    </Select>
                    <Submit
                        busy={f.processing}
                        label={tr(
                            locale,
                            "Create curriculum draft",
                            "إنشاء مسودة المنهج",
                        )}
                    />
                    <Errors errors={f.errors} />
                </form>
            </Card>
        </div>
    );
}
function Coordinator({ course, faculty }: { course: any; faculty: any[] }) {
    const f = useForm({ person_id: "", starts_on: "", ends_on: "" });
    return (
        <form
            className="mt-2 flex gap-2"
            onSubmit={(e) => {
                e.preventDefault();
                f.post(`/admin/courses/${course.id}/coordinators`);
            }}
        >
            <Select
                aria-label="Course coordinator"
                value={f.data.person_id}
                onChange={(e) => f.setData("person_id", e.target.value)}
            >
                <option value="">Assign coordinator</option>
                {faculty.map((x) => (
                    <option key={x.id} value={x.id}>
                        {x.given_name} {x.family_name}
                    </option>
                ))}
            </Select>
            <Submit busy={f.processing} label="Assign" />
        </form>
    );
}

function Academics(p: any) {
    const { locale } = usePage<Shared>().props;
    const period = useForm({
        code: "",
        name_en: "",
        name_ar: "",
        type: "semester",
        starts_on: "",
        ends_on: "",
        registration_opens_at: "",
        registration_closes_at: "",
    });
    const section = useForm({
        course_version_id: "",
        academic_period_id: "",
        campus_id: "",
        code: "",
        capacity: 30,
        waitlist_capacity: 0,
        delivery_mode: "in_person",
        instructors: [{ person_id: "", role: "primary" }],
        meetings: [] as any[],
        override_reason: "",
    });
    return (
        <AdminShell module="academics">
            <div className="grid gap-5 xl:grid-cols-2">
                <Card
                    title={tr(
                        locale,
                        "Create academic period",
                        "إنشاء فترة أكاديمية",
                    )}
                >
                    <form
                        className="grid gap-3 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            period.post("/admin/periods");
                        }}
                    >
                        <Text
                            placeholder="Code"
                            value={period.data.code}
                            onChange={(e) =>
                                period.setData("code", e.target.value)
                            }
                        />
                        <Select
                            value={period.data.type}
                            onChange={(e) =>
                                period.setData("type", e.target.value)
                            }
                        >
                            {[
                                "semester",
                                "trimester",
                                "quarter",
                                "summer",
                                "short_session",
                            ].map((x) => (
                                <option key={x}>{x}</option>
                            ))}
                        </Select>
                        <Text
                            placeholder="English name"
                            value={period.data.name_en}
                            onChange={(e) =>
                                period.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            dir="rtl"
                            placeholder="الاسم العربي"
                            value={period.data.name_ar}
                            onChange={(e) =>
                                period.setData("name_ar", e.target.value)
                            }
                        />
                        <Text
                            type="date"
                            value={period.data.starts_on}
                            onChange={(e) =>
                                period.setData("starts_on", e.target.value)
                            }
                        />
                        <Text
                            type="date"
                            value={period.data.ends_on}
                            onChange={(e) =>
                                period.setData("ends_on", e.target.value)
                            }
                        />
                        <Submit
                            busy={period.processing}
                            label={tr(locale, "Create period", "إنشاء")}
                        />
                        <Errors errors={period.errors} />
                    </form>
                </Card>
                <Card title={tr(locale, "Create section", "إنشاء شعبة")}>
                    <form
                        className="grid gap-3 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            section.post("/admin/sections");
                        }}
                    >
                        <Select
                            value={section.data.course_version_id}
                            onChange={(e) =>
                                section.setData(
                                    "course_version_id",
                                    e.target.value,
                                )
                            }
                        >
                            <option value="">Course</option>
                            {p.courseVersions.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.course.code} · {x.version}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={section.data.academic_period_id}
                            onChange={(e) =>
                                section.setData(
                                    "academic_period_id",
                                    e.target.value,
                                )
                            }
                        >
                            <option value="">Period</option>
                            {p.periods.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={section.data.campus_id}
                            onChange={(e) =>
                                section.setData("campus_id", e.target.value)
                            }
                        >
                            <option value="">Campus</option>
                            {p.campuses.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Text
                            placeholder="Section code"
                            value={section.data.code}
                            onChange={(e) =>
                                section.setData("code", e.target.value)
                            }
                        />
                        <Text
                            type="number"
                            value={section.data.capacity}
                            onChange={(e) =>
                                section.setData("capacity", +e.target.value)
                            }
                        />
                        <Select
                            value={section.data.instructors[0].person_id}
                            onChange={(e) =>
                                section.setData("instructors", [
                                    {
                                        person_id: e.target.value,
                                        role: "primary",
                                    },
                                ])
                            }
                        >
                            <option value="">Primary instructor</option>
                            {p.faculty.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.given_name} {x.family_name}
                                </option>
                            ))}
                        </Select>
                        <Submit
                            busy={section.processing}
                            label={tr(locale, "Create section", "إنشاء")}
                        />
                        <Errors errors={section.errors} />
                    </form>
                </Card>
            </div>
            <Card title={tr(locale, "Sections", "الشعب")}>
                {p.sections.map((x: any) => (
                    <div key={x.id} className="border-b p-3">
                        <b>{x.code}</b> · {x.course_version?.course?.title_en} ·{" "}
                        {x.academic_period?.code}{" "}
                        <span className="float-end">{x.capacity} seats</span>
                    </div>
                ))}
            </Card>
        </AdminShell>
    );
}

function People(p: any) {
    const { locale } = usePage<Shared>().props;
    const person = useForm({
        given_name: "",
        family_name: "",
        given_name_ar: "",
        family_name_ar: "",
        email: "",
        phone: "",
        department_id: "",
        staff_number: "",
        instructor_eligible: false,
    });
    return (
        <AdminShell module="people">
            <Card
                title={tr(
                    locale,
                    "Create staff or faculty record",
                    "إنشاء سجل موظف أو عضو هيئة تدريس",
                )}
            >
                <form
                    className="grid gap-3 md:grid-cols-3"
                    onSubmit={(e) => {
                        e.preventDefault();
                        person.post("/admin/people");
                    }}
                >
                    <Text
                        placeholder="Given name"
                        value={person.data.given_name}
                        onChange={(e) =>
                            person.setData("given_name", e.target.value)
                        }
                    />
                    <Text
                        placeholder="Family name"
                        value={person.data.family_name}
                        onChange={(e) =>
                            person.setData("family_name", e.target.value)
                        }
                    />
                    <Text
                        placeholder="Staff number"
                        value={person.data.staff_number}
                        onChange={(e) =>
                            person.setData("staff_number", e.target.value)
                        }
                    />
                    <Text
                        type="email"
                        placeholder="Email"
                        value={person.data.email}
                        onChange={(e) =>
                            person.setData("email", e.target.value)
                        }
                    />
                    <Select
                        value={person.data.department_id}
                        onChange={(e) =>
                            person.setData("department_id", e.target.value)
                        }
                    >
                        <option value="">Department</option>
                        {p.departments.map((x: any) => (
                            <option value={x.id} key={x.id}>
                                {x.code}
                            </option>
                        ))}
                    </Select>
                    <label className="self-center">
                        <input
                            type="checkbox"
                            checked={person.data.instructor_eligible}
                            onChange={(e) =>
                                person.setData(
                                    "instructor_eligible",
                                    e.target.checked,
                                )
                            }
                        />{" "}
                        {tr(locale, "Instructor eligible", "مؤهل للتدريس")}
                    </label>
                    <Submit
                        busy={person.processing}
                        label={tr(locale, "Create person", "إنشاء")}
                    />
                    <Errors errors={person.errors} />
                </form>
            </Card>
            <RoleBuilder locale={locale} permissions={p.permissions} />
            <Card
                title={tr(
                    locale,
                    "People and account activation",
                    "الأشخاص وتفعيل الحساب",
                )}
            >
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th className="p-2 text-start">Name</th>
                                <th className="p-2 text-start">Staff #</th>
                                <th className="p-2 text-start">Account</th>
                                <th className="p-2 text-start">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {p.people.map((x: any) => (
                                <PersonRow
                                    key={x.id}
                                    person={x}
                                    roles={p.roles}
                                />
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AdminShell>
    );
}
function RoleBuilder({
    locale,
    permissions,
}: {
    locale: string;
    permissions: string[];
}) {
    const f = useForm({
        code: "",
        name_en: "",
        name_ar: "",
        permissions: [] as string[],
        privileged: true,
    });
    return (
        <Card title={tr(locale, "Create custom role", "إنشاء دور مخصص")}>
            <form
                className="grid gap-3 md:grid-cols-3"
                onSubmit={(event) => {
                    event.preventDefault();
                    f.post("/admin/roles");
                }}
            >
                <Text
                    placeholder="Role code"
                    value={f.data.code}
                    onChange={(event) => f.setData("code", event.target.value)}
                />
                <Text
                    placeholder="English name"
                    value={f.data.name_en}
                    onChange={(event) =>
                        f.setData("name_en", event.target.value)
                    }
                />
                <Text
                    dir="rtl"
                    placeholder="الاسم العربي"
                    value={f.data.name_ar}
                    onChange={(event) =>
                        f.setData("name_ar", event.target.value)
                    }
                />
                <div className="grid gap-1 md:col-span-3 sm:grid-cols-2 lg:grid-cols-3">
                    {permissions.map((permission) => (
                        <label
                            key={permission}
                            className="rounded border p-2 text-sm"
                        >
                            <input
                                type="checkbox"
                                checked={f.data.permissions.includes(
                                    permission,
                                )}
                                onChange={(event) =>
                                    f.setData(
                                        "permissions",
                                        event.target.checked
                                            ? [
                                                  ...f.data.permissions,
                                                  permission,
                                              ]
                                            : f.data.permissions.filter(
                                                  (item) => item !== permission,
                                              ),
                                    )
                                }
                            />{" "}
                            {permission}
                        </label>
                    ))}
                </div>
                <Submit
                    busy={f.processing}
                    label={tr(locale, "Create role", "إنشاء الدور")}
                />
                <Errors errors={f.errors} />
            </form>
        </Card>
    );
}
function PersonRow({ person, roles }: { person: any; roles: any[] }) {
    const f = useForm({
        email: person.email || "",
        role_id: "",
        campus_id: "",
        department_id: person.department_id || "",
        program_id: "",
    });
    return (
        <tr className="border-t">
            <td className="p-2">
                {person.given_name} {person.family_name}
            </td>
            <td className="p-2">{person.staff_number || "—"}</td>
            <td className="p-2">{person.user?.email || "Not active"}</td>
            <td className="p-2">
                {!person.user && (
                    <form
                        className="flex min-w-[28rem] gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            f.post(`/admin/people/${person.id}/activate`);
                        }}
                    >
                        <Text
                            type="email"
                            placeholder="Login email"
                            value={f.data.email}
                            onChange={(e) => f.setData("email", e.target.value)}
                        />
                        <Select
                            value={f.data.role_id}
                            onChange={(e) =>
                                f.setData("role_id", e.target.value)
                            }
                        >
                            <option value="">Role</option>
                            {roles.map((x) => (
                                <option key={x.id} value={x.id}>
                                    {x.name_en}
                                </option>
                            ))}
                        </Select>
                        <Submit busy={f.processing} label="Activate" />
                    </form>
                )}
            </td>
        </tr>
    );
}

function Admissions(p: any) {
    const { locale } = usePage<Shared>().props;
    const form = useForm({
        code: "",
        name_en: "",
        name_ar: "",
        sections: [
            {
                title_en: "Personal information",
                title_ar: "المعلومات الشخصية",
                fields: [
                    {
                        key: "statement",
                        type: "long_text",
                        label_en: "Personal statement",
                        label_ar: "البيان الشخصي",
                        required: true,
                        options: [],
                        visibility_rules: [],
                    },
                ],
            },
        ],
    });
    const published = p.forms
        .flatMap((x: any) => x.versions || [])
        .filter((x: any) => x.status === "published");
    const cycle = useForm({
        program_id: "",
        campus_id: "",
        intake_period_id: "",
        form_version_id: "",
        code: "",
        name_en: "",
        name_ar: "",
        quota: 100,
        opens_at: "",
        closes_at: "",
        decision_deadline: "",
        acceptance_deadline: "",
        application_fee: 0,
        currency: "USD",
        required_documents: ["identity", "transcript"],
    });
    return (
        <AdminShell module="admissions">
            <div className="grid gap-5 xl:grid-cols-2">
                <Card
                    title={tr(
                        locale,
                        "Create application form",
                        "إنشاء نموذج طلب",
                    )}
                >
                    <form
                        className="grid gap-3"
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post("/admin/admission-forms");
                        }}
                    >
                        <Text
                            placeholder="Form code"
                            value={form.data.code}
                            onChange={(e) =>
                                form.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English name"
                            value={form.data.name_en}
                            onChange={(e) =>
                                form.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            dir="rtl"
                            placeholder="الاسم العربي"
                            value={form.data.name_ar}
                            onChange={(e) =>
                                form.setData("name_ar", e.target.value)
                            }
                        />
                        {form.data.sections.map((section, sectionIndex) => (
                            <div
                                key={sectionIndex}
                                className="rounded-xl border p-3"
                            >
                                <div className="grid gap-2 md:grid-cols-2">
                                    <Text
                                        placeholder="Section title"
                                        value={section.title_en}
                                        onChange={(event) =>
                                            form.setData(
                                                "sections",
                                                form.data.sections.map(
                                                    (item, index) =>
                                                        index === sectionIndex
                                                            ? {
                                                                  ...item,
                                                                  title_en:
                                                                      event
                                                                          .target
                                                                          .value,
                                                              }
                                                            : item,
                                                ),
                                            )
                                        }
                                    />
                                    <Text
                                        dir="rtl"
                                        placeholder="عنوان القسم"
                                        value={section.title_ar}
                                        onChange={(event) =>
                                            form.setData(
                                                "sections",
                                                form.data.sections.map(
                                                    (item, index) =>
                                                        index === sectionIndex
                                                            ? {
                                                                  ...item,
                                                                  title_ar:
                                                                      event
                                                                          .target
                                                                          .value,
                                                              }
                                                            : item,
                                                ),
                                            )
                                        }
                                    />
                                </div>
                                {section.fields.map((field, fieldIndex) => (
                                    <div
                                        key={fieldIndex}
                                        className="mt-2 grid gap-2 rounded bg-slate-50 p-2 md:grid-cols-4"
                                    >
                                        <Text
                                            placeholder="Field key"
                                            value={field.key}
                                            onChange={(event) =>
                                                form.setData(
                                                    "sections",
                                                    form.data.sections.map(
                                                        (item, index) =>
                                                            index ===
                                                            sectionIndex
                                                                ? {
                                                                      ...item,
                                                                      fields: item.fields.map(
                                                                          (
                                                                              candidate,
                                                                              candidateIndex,
                                                                          ) =>
                                                                              candidateIndex ===
                                                                              fieldIndex
                                                                                  ? {
                                                                                        ...candidate,
                                                                                        key: event
                                                                                            .target
                                                                                            .value,
                                                                                    }
                                                                                  : candidate,
                                                                      ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <Select
                                            value={field.type}
                                            onChange={(event) =>
                                                form.setData(
                                                    "sections",
                                                    form.data.sections.map(
                                                        (item, index) =>
                                                            index ===
                                                            sectionIndex
                                                                ? {
                                                                      ...item,
                                                                      fields: item.fields.map(
                                                                          (
                                                                              candidate,
                                                                              candidateIndex,
                                                                          ) =>
                                                                              candidateIndex ===
                                                                              fieldIndex
                                                                                  ? {
                                                                                        ...candidate,
                                                                                        type: event
                                                                                            .target
                                                                                            .value,
                                                                                    }
                                                                                  : candidate,
                                                                      ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        >
                                            {[
                                                "text",
                                                "long_text",
                                                "number",
                                                "date",
                                                "choice",
                                                "multi_choice",
                                                "yes_no",
                                                "address",
                                                "education_history",
                                                "document",
                                                "declaration",
                                                "consent",
                                            ].map((type) => (
                                                <option key={type}>
                                                    {type}
                                                </option>
                                            ))}
                                        </Select>
                                        <Text
                                            placeholder="English label"
                                            value={field.label_en}
                                            onChange={(event) =>
                                                form.setData(
                                                    "sections",
                                                    form.data.sections.map(
                                                        (item, index) =>
                                                            index ===
                                                            sectionIndex
                                                                ? {
                                                                      ...item,
                                                                      fields: item.fields.map(
                                                                          (
                                                                              candidate,
                                                                              candidateIndex,
                                                                          ) =>
                                                                              candidateIndex ===
                                                                              fieldIndex
                                                                                  ? {
                                                                                        ...candidate,
                                                                                        label_en:
                                                                                            event
                                                                                                .target
                                                                                                .value,
                                                                                    }
                                                                                  : candidate,
                                                                      ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <Text
                                            dir="rtl"
                                            placeholder="التسمية العربية"
                                            value={field.label_ar}
                                            onChange={(event) =>
                                                form.setData(
                                                    "sections",
                                                    form.data.sections.map(
                                                        (item, index) =>
                                                            index ===
                                                            sectionIndex
                                                                ? {
                                                                      ...item,
                                                                      fields: item.fields.map(
                                                                          (
                                                                              candidate,
                                                                              candidateIndex,
                                                                          ) =>
                                                                              candidateIndex ===
                                                                              fieldIndex
                                                                                  ? {
                                                                                        ...candidate,
                                                                                        label_ar:
                                                                                            event
                                                                                                .target
                                                                                                .value,
                                                                                    }
                                                                                  : candidate,
                                                                      ),
                                                                  }
                                                                : item,
                                                    ),
                                                )
                                            }
                                        />
                                        <label>
                                            <input
                                                type="checkbox"
                                                checked={field.required}
                                                onChange={(event) =>
                                                    form.setData(
                                                        "sections",
                                                        form.data.sections.map(
                                                            (item, index) =>
                                                                index ===
                                                                sectionIndex
                                                                    ? {
                                                                          ...item,
                                                                          fields: item.fields.map(
                                                                              (
                                                                                  candidate,
                                                                                  candidateIndex,
                                                                              ) =>
                                                                                  candidateIndex ===
                                                                                  fieldIndex
                                                                                      ? {
                                                                                            ...candidate,
                                                                                            required:
                                                                                                event
                                                                                                    .target
                                                                                                    .checked,
                                                                                        }
                                                                                      : candidate,
                                                                          ),
                                                                      }
                                                                    : item,
                                                        ),
                                                    )
                                                }
                                            />{" "}
                                            Required
                                        </label>
                                    </div>
                                ))}
                                <button
                                    type="button"
                                    className="mt-2 text-sm font-bold text-indigo-700"
                                    onClick={() =>
                                        form.setData(
                                            "sections",
                                            form.data.sections.map(
                                                (item, index) =>
                                                    index === sectionIndex
                                                        ? {
                                                              ...item,
                                                              fields: [
                                                                  ...item.fields,
                                                                  {
                                                                      key: "",
                                                                      type: "text",
                                                                      label_en:
                                                                          "",
                                                                      label_ar:
                                                                          "",
                                                                      required: false,
                                                                      options:
                                                                          [],
                                                                      visibility_rules:
                                                                          [],
                                                                  },
                                                              ],
                                                          }
                                                        : item,
                                            ),
                                        )
                                    }
                                >
                                    + {tr(locale, "Add field", "إضافة حقل")}
                                </button>
                            </div>
                        ))}
                        <button
                            type="button"
                            className="text-start text-sm font-bold text-indigo-700"
                            onClick={() =>
                                form.setData("sections", [
                                    ...form.data.sections,
                                    {
                                        title_en: "",
                                        title_ar: "",
                                        fields: [
                                            {
                                                key: "",
                                                type: "text",
                                                label_en: "",
                                                label_ar: "",
                                                required: false,
                                                options: [],
                                                visibility_rules: [],
                                            },
                                        ],
                                    },
                                ])
                            }
                        >
                            + {tr(locale, "Add section", "إضافة قسم")}
                        </button>
                        <Submit
                            busy={form.processing}
                            label={tr(
                                locale,
                                "Create draft form",
                                "إنشاء مسودة",
                            )}
                        />
                        <Errors errors={form.errors} />
                    </form>
                    {p.forms.map((x: any) => (
                        <div key={x.id} className="mt-3 rounded border p-3">
                            <b>
                                {x.code} · {x.name_en}
                            </b>
                            {x.versions.map((v: any) => (
                                <span key={v.id} className="ms-3 text-sm">
                                    v{v.version} · {v.status}{" "}
                                    {v.status === "draft" && (
                                        <button
                                            className="text-indigo-700"
                                            onClick={() =>
                                                router.post(
                                                    `/admin/admission-form-versions/${v.id}/publish`,
                                                    {
                                                        reason: "Approved for admission use",
                                                    },
                                                )
                                            }
                                        >
                                            Publish
                                        </button>
                                    )}
                                </span>
                            ))}
                        </div>
                    ))}
                </Card>
                <Card
                    title={tr(
                        locale,
                        "Create admission cycle",
                        "إنشاء دورة قبول",
                    )}
                >
                    <form
                        className="grid gap-3 md:grid-cols-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            cycle.post("/admin/admission-cycles");
                        }}
                    >
                        <Select
                            value={cycle.data.program_id}
                            onChange={(e) =>
                                cycle.setData("program_id", e.target.value)
                            }
                        >
                            <option value="">Program</option>
                            {p.programs.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={cycle.data.campus_id}
                            onChange={(e) =>
                                cycle.setData("campus_id", e.target.value)
                            }
                        >
                            <option value="">Campus</option>
                            {p.campuses.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={cycle.data.intake_period_id}
                            onChange={(e) =>
                                cycle.setData(
                                    "intake_period_id",
                                    e.target.value,
                                )
                            }
                        >
                            <option value="">Intake</option>
                            {p.periods.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    {x.code}
                                </option>
                            ))}
                        </Select>
                        <Select
                            value={cycle.data.form_version_id}
                            onChange={(e) =>
                                cycle.setData("form_version_id", e.target.value)
                            }
                        >
                            <option value="">Published form</option>
                            {published.map((x: any) => (
                                <option key={x.id} value={x.id}>
                                    Version {x.version}
                                </option>
                            ))}
                        </Select>
                        <Text
                            placeholder="Cycle code"
                            value={cycle.data.code}
                            onChange={(e) =>
                                cycle.setData("code", e.target.value)
                            }
                        />
                        <Text
                            placeholder="English name"
                            value={cycle.data.name_en}
                            onChange={(e) =>
                                cycle.setData("name_en", e.target.value)
                            }
                        />
                        <Text
                            type="datetime-local"
                            value={cycle.data.opens_at}
                            onChange={(e) =>
                                cycle.setData("opens_at", e.target.value)
                            }
                        />
                        <Text
                            type="datetime-local"
                            value={cycle.data.closes_at}
                            onChange={(e) =>
                                cycle.setData("closes_at", e.target.value)
                            }
                        />
                        <Text
                            type="number"
                            min="1"
                            value={cycle.data.quota}
                            onChange={(e) =>
                                cycle.setData("quota", +e.target.value)
                            }
                        />
                        <Text
                            type="number"
                            min="0"
                            step=".01"
                            value={cycle.data.application_fee}
                            onChange={(e) =>
                                cycle.setData(
                                    "application_fee",
                                    +e.target.value,
                                )
                            }
                        />
                        <Submit
                            busy={cycle.processing}
                            label={tr(locale, "Create cycle", "إنشاء")}
                        />
                        <Errors errors={cycle.errors} />
                    </form>
                </Card>
            </div>
            <Card
                title={tr(locale, "Admission work queue", "قائمة عمل القبول")}
            >
                <div className="overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr>
                                <th>Application</th>
                                <th>Applicant</th>
                                <th>Program</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {p.applications.map((x: any) => (
                                <tr className="border-t" key={x.id}>
                                    <td className="p-2">
                                        {x.application_number}
                                    </td>
                                    <td>
                                        {x.person?.given_name}{" "}
                                        {x.person?.family_name}
                                    </td>
                                    <td>{x.program?.code}</td>
                                    <td>{x.status}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AdminShell>
    );
}

function Matriculation(p: any) {
    const { locale } = usePage<Shared>().props;
    return (
        <AdminShell module="matriculation">
            <Card
                title={tr(
                    locale,
                    "Accepted applicants awaiting registrar approval",
                    "المتقدمون المقبولون بانتظار موافقة المسجل",
                )}
            >
                {p.pending.map((x: any) => (
                    <MatriculationRow
                        key={x.id}
                        item={x}
                        campuses={p.campuses}
                        periods={p.periods}
                        curricula={p.curricula.filter(
                            (c: any) =>
                                c.program_id === x.application.program_id,
                        )}
                    />
                ))}
                {!p.pending.length && (
                    <p className="text-slate-500">
                        {tr(
                            locale,
                            "No pending matriculations.",
                            "لا توجد طلبات معلقة.",
                        )}
                    </p>
                )}
            </Card>
        </AdminShell>
    );
}
function MatriculationRow({
    item,
    campuses,
    periods,
    curricula,
}: {
    item: any;
    campuses: any[];
    periods: any[];
    curricula: any[];
}) {
    const f = useForm({
        curriculum_version_id: item.curriculum_version_id || "",
        campus_id: item.campus_id || "",
        intake_period_id: item.intake_period_id || "",
        started_on: new Date().toISOString().slice(0, 10),
        create_term_enrollment: true,
        credit_limit: 18,
        override_reason: "",
    });
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                f.post(`/admin/matriculation/${item.id}/approve`);
            }}
            className="mb-4 grid gap-3 rounded-xl border p-4 md:grid-cols-3"
        >
            <div className="md:col-span-3">
                <b>
                    {item.application.application_number} ·{" "}
                    {item.application.person.given_name}{" "}
                    {item.application.person.family_name}
                </b>
                <span className="float-end rounded bg-amber-100 px-2 text-xs">
                    {item.status}
                </span>
            </div>
            <Select
                value={f.data.curriculum_version_id}
                onChange={(e) =>
                    f.setData("curriculum_version_id", e.target.value)
                }
            >
                <option value="">Curriculum</option>
                {curricula.map((x) => (
                    <option key={x.id} value={x.id}>
                        {x.version}
                    </option>
                ))}
            </Select>
            <Select
                value={f.data.campus_id}
                onChange={(e) => f.setData("campus_id", e.target.value)}
            >
                <option value="">Campus</option>
                {campuses.map((x) => (
                    <option key={x.id} value={x.id}>
                        {x.code}
                    </option>
                ))}
            </Select>
            <Select
                value={f.data.intake_period_id}
                onChange={(e) => f.setData("intake_period_id", e.target.value)}
            >
                <option value="">Intake term</option>
                {periods.map((x) => (
                    <option key={x.id} value={x.id}>
                        {x.code}
                    </option>
                ))}
            </Select>
            <Text
                type="date"
                value={f.data.started_on}
                onChange={(e) => f.setData("started_on", e.target.value)}
            />
            <label>
                <input
                    type="checkbox"
                    checked={f.data.create_term_enrollment}
                    onChange={(e) =>
                        f.setData("create_term_enrollment", e.target.checked)
                    }
                />{" "}
                Create first term enrollment
            </label>
            <Submit
                busy={f.processing}
                label="Approve & issue student number"
            />
            <Errors errors={f.errors} />
        </form>
    );
}

function Audit(p: any) {
    const { locale } = usePage<Shared>().props;
    const events = p.events?.data || [];
    return (
        <AdminShell module="audit">
            <Card
                title={tr(
                    locale,
                    "Immutable administrative activity",
                    "النشاط الإداري غير القابل للتغيير",
                )}
            >
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr>
                                <th className="p-2 text-start">Time</th>
                                <th className="text-start">Actor</th>
                                <th className="text-start">Action</th>
                                <th className="text-start">Subject</th>
                                <th className="text-start">Reason</th>
                            </tr>
                        </thead>
                        <tbody>
                            {events.map((x: any) => (
                                <tr key={x.id} className="border-t">
                                    <td className="p-2 whitespace-nowrap">
                                        {x.occurred_at}
                                    </td>
                                    <td>{x.actor?.name || "System"}</td>
                                    <td>{x.action}</td>
                                    <td>
                                        {x.subject_type?.split("\\").pop()} ·{" "}
                                        {x.subject_id}
                                    </td>
                                    <td>{x.reason || "—"}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </AdminShell>
    );
}

export function AdminPortal(props: any) {
    return (
        (
            {
                dashboard: Dashboard,
                college: College,
                catalog: Catalog,
                academics: Academics,
                people: People,
                admissions: Admissions,
                matriculation: Matriculation,
                audit: Audit,
            } as any
        )[props.module]?.(props) || <Dashboard {...props} />
    );
}

export function Mfa() {
    const { locale } = usePage<Shared>().props;
    const f = useForm({ code: "" });
    return (
        <main
            dir={locale === "ar" ? "rtl" : "ltr"}
            className="grid min-h-screen place-items-center bg-indigo-950 p-6"
        >
            <div className="w-full max-w-md rounded-2xl bg-white p-8">
                <h1 className="text-2xl font-black">
                    {tr(locale, "Staff verification", "التحقق من الموظف")}
                </h1>
                <p className="my-4 text-slate-600">
                    {tr(
                        locale,
                        "Send a six-digit code to your verified staff email, or enter a recovery code.",
                        "أرسل رمزاً من ستة أرقام إلى بريد الموظف أو أدخل رمز استرداد.",
                    )}
                </p>
                <button
                    className="mb-4 rounded border px-4 py-2"
                    onClick={() => router.post("/mfa/send")}
                >
                    {tr(locale, "Send code", "إرسال الرمز")}
                </button>
                <form
                    onSubmit={(e) => {
                        e.preventDefault();
                        f.post("/mfa/verify");
                    }}
                >
                    <Text
                        maxLength={32}
                        value={f.data.code}
                        onChange={(e) => f.setData("code", e.target.value)}
                    />
                    <Errors errors={f.errors} />
                    <div className="mt-4">
                        <Submit
                            busy={f.processing}
                            label={tr(locale, "Verify", "تحقق")}
                        />
                    </div>
                </form>
            </div>
        </main>
    );
}
export function ChangePassword() {
    const { locale } = usePage<Shared>().props;
    const f = useForm({ password: "", password_confirmation: "" });
    return (
        <main
            dir={locale === "ar" ? "rtl" : "ltr"}
            className="grid min-h-screen place-items-center bg-indigo-950 p-6"
        >
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    f.put("/change-password");
                }}
                className="w-full max-w-md space-y-4 rounded-2xl bg-white p-8"
            >
                <h1 className="text-2xl font-black">
                    {tr(locale, "Set a new password", "تعيين كلمة مرور جديدة")}
                </h1>
                <Text
                    type="password"
                    placeholder="New password"
                    value={f.data.password}
                    onChange={(e) => f.setData("password", e.target.value)}
                />
                <Text
                    type="password"
                    placeholder="Confirm password"
                    value={f.data.password_confirmation}
                    onChange={(e) =>
                        f.setData("password_confirmation", e.target.value)
                    }
                />
                <Errors errors={f.errors} />
                <Submit
                    busy={f.processing}
                    label={tr(locale, "Change password", "تغيير كلمة المرور")}
                />
            </form>
        </main>
    );
}
