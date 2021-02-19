fname = '../json/speedOverTime.json'; 
fid = fopen(fname); 
raw = fread(fid,inf); 
str = char(raw'); 
fclose(fid); 
val = jsondecode(str);

speedOverTime_x = val(:,1)-1612811700;

speedOverTime_y = val(:,2);


p = plot(speedOverTime_x,speedOverTime_y);

p.LineWidth = 2;
fontSize = 18;
title("Geschwindigkeit in Abhähngigkeit der Zeit", 'FontSize', fontSize);
xlabel("Strecke [m]", 'FontSize', fontSize);
ylabel("Zeit [s]", 'FontSize', fontSize);
x0=10;
y0=10;
width=1100;
height=600;
axis([-10 60 0 55])
set(gcf,'position',[x0,y0,width,height]);
set(gca, 'FontSize', 14);
t = gca;
exportgraphics(t,'SpeedOverTime.jpg','Resolution',300);
