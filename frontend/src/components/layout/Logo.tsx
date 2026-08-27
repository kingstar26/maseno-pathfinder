import logo from "@/assets/maseno-crest.jpeg";

export function MasenoLogo({
  size = 40,
  showText = true,
  light = false,
}: {
  size?: number;
  showText?: boolean;
  light?: boolean;
}) {
  return (
    <div className="flex items-center gap-3">
      <img
        src={logo}
        alt="Maseno University crest"
        width={size}
        height={size}
        style={{ width: size, height: size, objectFit: "contain" }}
        className="shrink-0 rounded-full bg-white p-0.5"
      />
      {showText && (
        <div className="flex min-w-0 flex-col leading-tight">
          <span
            className={`truncate text-base font-bold tracking-tight sm:text-lg ${light ? "text-white" : "text-foreground"}`}
          >
            Maseno University
          </span>
          <span className="mt-0.5 truncate text-[10px] font-semibold tracking-[0.12em] text-gold sm:text-xs">
            Fountain of Excellence
          </span>
        </div>
      )}
    </div>
  );
}
