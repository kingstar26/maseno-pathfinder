import type { Section } from "./ModulePage";

type ModuleSpec = { title: string; subtitle: string; sections: Section[] };
export const moduleSpecs: Record<string, ModuleSpec> = {
  profile: {
    title: "Graduate Profile",
    subtitle: "Manage the information that supports your graduate journey.",
    sections: [
      {
        title: "Personal & contact information",
        description: "Identity, contact and profile picture records.",
        fields: ["Legal name", "Contact details", "Profile picture", "CV upload"],
      },
      {
        title: "Academic & graduation details",
        description: "University qualification records.",
        fields: ["Faculty", "Department", "Programme", "Graduation details"],
      },
      {
        title: "Career profile",
        description: "Professional development and career information.",
        fields: [
          "Employment status",
          "Employment history",
          "Career progression",
          "Business information",
        ],
      },
      {
        title: "Professional development",
        description: "Skills, certifications and further study records.",
        fields: ["Further studies", "Skills", "Certifications"],
      },
      {
        title: "Graduate activity",
        description: "Profile completion, survey and recommendation activity.",
        fields: [
          "Profile completion",
          "Survey status",
          "Recommendation status",
          "Activity history",
        ],
      },
      {
        title: "Career insights",
        description: "Backend-generated career statistics, analytics and timeline.",
        fields: ["Career statistics", "Employment analytics", "Charts", "Timeline"],
      },
    ],
  },
  employment: {
    title: "Employment",
    subtitle: "Keep your employment history and career progression current.",
    sections: [
      {
        title: "Current employment",
        description: "Your active employer and employment status.",
        fields: ["Employer", "Position", "Employment status", "Salary range"],
      },
      {
        title: "Employment timeline",
        description: "Chronological employment history.",
        fields: ["Employer", "Role", "Start date", "End date"],
      },
      {
        title: "Career growth",
        description: "Career progression and employment analytics.",
        fields: ["Progression", "Analytics", "Career insights"],
      },
    ],
  },
  business: {
    title: "Business",
    subtitle: "Businesses you own or co-own.",
    sections: [
      {
        title: "Business details",
        description: "Register and maintain your business information.",
        fields: ["Business name", "Industry", "Registration number", "Location"],
      },
      {
        title: "Business profile",
        description: "Description, documentation and verification status.",
        fields: ["Description", "Business documents", "Status"],
      },
    ],
  },
  "further-studies": {
    title: "Further Studies",
    subtitle: "Postgraduate and continuing education records.",
    sections: [
      {
        title: "Study records",
        description: "Qualifications currently underway or completed.",
        fields: ["Institution", "Course", "Level", "Country"],
      },
      {
        title: "Completion details",
        description: "Dates and study completion status.",
        fields: ["Completion status", "Start date", "End date"],
      },
    ],
  },
  survey: {
    title: "Tracer Survey",
    subtitle: "Complete your Maseno graduate tracer survey.",
    sections: [
      {
        title: "Survey progress",
        description: "Your available survey and completion progress.",
        fields: ["Progress tracking", "Validation", "Save progress", "Completion status"],
      },
      {
        title: "Survey responses",
        description: "Submitted and saved tracer responses.",
        fields: ["Response history", "Submission status"],
      },
    ],
  },
  recommendations: {
    title: "Recommendation Letters",
    subtitle: "Request and track official recommendation letters.",
    sections: [
      {
        title: "Letter requests",
        description: "Create and monitor official letter requests.",
        fields: ["Purpose", "Approval status", "Processing status"],
      },
      {
        title: "Issued letters",
        description: "Officially issued documents and verification.",
        fields: ["Download", "History", "QR verification"],
      },
    ],
  },
  announcements: {
    title: "Announcements",
    subtitle: "Official announcements and updates from Maseno University.",
    sections: [
      {
        title: "Announcement feed",
        description: "Institutional announcements filtered by category and read status.",
        fields: ["Categories", "Read status", "Search"],
      },
    ],
  },
  events: {
    title: "Events",
    subtitle: "Upcoming alumni and university events.",
    sections: [
      {
        title: "Event calendar",
        description: "Upcoming and past university events.",
        fields: ["Upcoming events", "Past events", "Registration"],
      },
    ],
  },
  notifications: {
    title: "Notifications",
    subtitle: "Your MUGTS activity notifications.",
    sections: [
      {
        title: "Notification centre",
        description: "Categorised notifications with read-state controls.",
        fields: ["Unread count", "Categories", "Mark as read"],
      },
    ],
  },
  reports: {
    title: "Reports",
    subtitle: "Personal reports and export-ready information.",
    sections: [
      {
        title: "Report library",
        description: "Backend-generated reports, statistics and visualisations.",
        fields: ["Reports", "Charts", "Statistics", "Search"],
      },
      {
        title: "Export controls",
        description: "Filter data before exporting an official report.",
        fields: ["PDF export", "Excel export", "Date filters"],
      },
    ],
  },
  settings: {
    title: "Settings",
    subtitle: "Manage your account and portal preferences.",
    sections: [
      {
        title: "Profile settings",
        description: "Control the account information shared with MUGTS.",
        fields: ["Profile settings", "Account preferences"],
      },
      {
        title: "Security",
        description: "Protect access to your graduate account.",
        fields: ["Password change", "Security settings"],
      },
      {
        title: "Preferences",
        description: "Set how MUGTS communicates and appears.",
        fields: ["Notification preferences", "Theme preferences"],
      },
    ],
  },
};
